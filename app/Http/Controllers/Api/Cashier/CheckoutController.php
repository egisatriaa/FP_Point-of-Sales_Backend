<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\MidtransService;
use OpenApi\Annotations as OA;

/**
 * Checkout Controller
 * Handles conversion of Active Cart to Transaction
 */
class CheckoutController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Post(
     *     path="/checkout",
     *     summary="Checkout Active Cart",
     *     description="Memproses checkout dari cart yang sedang aktif (milik user login). Stock akan berkurang dan status cart menjadi 'completed'.",
     *     tags={"Checkout"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Checkout Payment Data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"payment_method", "paid_amount"},
     *                 @OA\Property(property="payment_method", type="string", example="cash", description="Metode pembayaran (cash/qris)"),
     *                 @OA\Property(property="paid_amount", type="number", example=50000, description="Uang yang dibayarkan")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Checkout Successful (Transaction Created)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Checkout berhasil"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction_id", type="integer", example=101),
     *                 @OA\Property(property="transaction_code", type="string", example="TRX-2026-XYZ"),
     *                 @OA\Property(property="total_amount", type="number", example=45000),
     *                 @OA\Property(property="paid_amount", type="number", example=50000),
     *                 @OA\Property(property="change_amount", type="number", example=5000),
     *                 @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request (Cart empty, stock insufficient, payment deficient)"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict (Stock changed during checkout)"
     *     )
     * )
     */
    public function store(Request $request)
    {
        // 1. Validasi Input Basic
        $validated = $request->validate([
            'payment_method' => 'required|string',
            'paid_amount'    => 'required|numeric|min:0',
        ]);

        $isMidtrans = $validated['payment_method'] === 'midtrans';

        $user = $request->user();

        // 2. Ambil Active Cart + Items
        $cart = Cart::with(['items.product'])
            ->active()
            ->where('user_id', $user->id)
            ->first();

        // 3. Validasi Keberadaan Cart
        if (!$cart) {
            return $this->error('Tidak ada cart aktif ditemukan. Silakan tambahkan produk ke keranjang.', 404);
        }

        if ($cart->items->isEmpty()) {
            return $this->error('Cart kosong. Tidak ada item untuk di-checkout.', 400);
        }
        
        // 4. Hitung Total Amount & Validasi Stock
        $totalAmount = 0;
        
        foreach ($cart->items as $item) {
            // Gunakan Price Snapshot dari CartItem
            $subtotal = $item->price * $item->quantity;
            $totalAmount += $subtotal;

            // Validasi Stock Real-time
            if ($item->product->stock < $item->quantity) {
                return $this->error("Stock produk '{$item->product->product_name}' tidak mencukupi. Tersedia: {$item->product->stock}, Diminta: {$item->quantity}.", 409);
            }
        }

        // 5. Validasi Pembayaran (Skip for Midtrans)
        if (!$isMidtrans && $validated['paid_amount'] < $totalAmount) {
            return $this->error("Uang pembayaran kurang. Total: {$totalAmount}, Dibayar: {$validated['paid_amount']}", 400);
        }

        $changeAmount = $isMidtrans ? 0 : ($validated['paid_amount'] - $totalAmount);
        
        // For Midtrans, payment_amount stored is the expected amount
        $paymentAmount = $validated['paid_amount'];
        if ($isMidtrans) {
             $paymentAmount = $totalAmount;
        }

        // 6. DB Transaction (Atomic Process)
        return DB::transaction(function () use ($user, $cart, $validated, $totalAmount, $changeAmount, $paymentAmount, $isMidtrans) {
            try {
                // Determine initial status
                $initialStatus = $isMidtrans ? 'pending' : 'completed';

                // A. Create Transaction Header
                $transaction = Transaction::create([
                    'user_id'          => $user->id,
                    'transaction_code' => 'TRX-' . now()->format('YmdHis') . '-' . strtoupper(uniqid()),
                    'transaction_date' => now(),
                    'total_amount'     => $totalAmount,
                    'payment_amount'   => $paymentAmount,
                    'change_amount'    => $changeAmount,
                    'payment_method'   => $validated['payment_method'],
                    'status'           => $initialStatus,
                    'paid_at'          => $isMidtrans ? null : now(),
                ]);

                // B. Move Items & Deduct Stock
                foreach ($cart->items as $item) {
                    // Create Detail
                    TransactionDetail::create([
                        'transaction_id' => $transaction->id,
                        'product_id'     => $item->product_id,
                        'quantity'       => $item->quantity,
                        'price_at_transaction' => $item->price, // Snapshot
                        'subtotal'       => $item->price * $item->quantity
                    ]);

                    // Deduct Stock (Even for pending Midtrans, we reserve stock)
                    $item->product->decrement('stock', $item->quantity);
                }

                // C. Update Cart Status -> Completed
                $cart->update([
                    'status' => Cart::STATUS_COMPLETED
                ]);

                // D. Midtrans Integration
                $snapToken = null;
                if ($isMidtrans) {
                    $midtransService = new MidtransService();
                    $snapToken = $midtransService->createSnapToken($transaction);
                    
                    $transaction->update(['snap_token' => $snapToken]);
                }

                // E. Return Response Data
                return $this->success([
                    'transaction_id'   => $transaction->id,
                    'cart_id'          => $cart->id,
                    'transaction_code' => $transaction->transaction_code,
                    'payment_method'   => $validated['payment_method'],
                    'snap_token'       => $snapToken,
                    'total_amount'     => (float) $totalAmount,
                    'paid_amount'      => (float) $paymentAmount,
                    'change_amount'    => (float) $changeAmount,
                    'status'           => $initialStatus,
                    'created_at'       => $transaction->transaction_date->toDateTimeString(),
                    'items'            => $cart->items->map(fn($item) => [
                        'product_id'     => $item->product_id,
                        'product_name'   => $item->product->product_name, // Optional fallback
                        'price'          => (float) $item->price,
                        'quantity'       => (int) $item->quantity,
                        'subtotal'       => (float) ($item->price * $item->quantity)
                    ])
                ], 'Checkout berhasil');

            } catch (\Exception $e) {
                // Manually re-throw check or handle basic DB errors to avoid stuck transactions?
                // Standard Laravel DB::transaction rolls back automatically on exception.
                throw $e;
            }
        });
    }
}
