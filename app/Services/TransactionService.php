<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Exceptions\BusinessException;

class TransactionService
{
    /**
     * Proses checkout transaksi
     */
    public function checkout(int $userId, float $paymentAmount): Transaction
    {
        return DB::transaction(function () use ($userId, $paymentAmount) {

            /**
             * 1️ Ambil cart user
             * Cart adalah pre-transaction state
             */
            $carts = Cart::where('user_id', $userId)->get();

            if ($carts->isEmpty()) {
                throw new BusinessException('Cart is empty');
            }

            $totalAmount = 0;
            $lockedProducts = [];

            /**
             * 2️ Validasi cart + LOCK produk (concurrency safe)
             */
            foreach ($carts as $cart) {

                // Lock produk agar tidak diubah transaksi lain
                $product = Product::where('id', $cart->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw new BusinessException('Product not found');
                }

                if ($product->stock < $cart->quantity) {
                    throw new BusinessException("Stock not sufficient for {$product->product_name}");
                }

                // Hitung total menggunakan harga real-time dari DB
                $totalAmount += $product->price * $cart->quantity;

                // Simpan instance product agar tidak query ulang
                $lockedProducts[$cart->id] = $product;
            }

            /**
             * 3️ Validasi pembayaran
             */
            if ($paymentAmount < $totalAmount) {
                throw new BusinessException('Payment amount is insufficient');
            }

            /**
             * 4️ Buat transaksi (FINAL & IMMUTABLE)
             */
            $transaction = Transaction::create([
                'transaction_code' => $this->generateTransactionCode(),
                'transaction_date' => now(),
                'total_amount'     => $totalAmount,
                'payment_amount'   => $paymentAmount,
                'change_amount'    => $paymentAmount - $totalAmount,
                'status'           => Transaction::STATUS_COMPLETED,
                'user_id'          => $userId,
            ]);

            /**
             * 5️ Simpan detail transaksi + kurangi stok
             */
            foreach ($carts as $cart) {
                $product = $lockedProducts[$cart->id];

                TransactionDetail::create([
                    'transaction_id'       => $transaction->id,
                    'product_id'           => $product->id,
                    'quantity'             => $cart->quantity,
                    'price_at_transaction' => $product->price, // snapshot harga
                    'subtotal'             => $product->price * $cart->quantity,
                ]);

                // Stok dikurangi SETELAH transaksi dibuat
                $product->decrement('stock', $cart->quantity);
            }

            /**
             * 6️ Bersihkan cart (pre-transaction selesai)
             */
            Cart::where('user_id', $userId)->delete();

            return $transaction;
        });
    }

    /**
     * Generate human-readable, date-based, collision-safe transaction code.
     * Format: TRX-YYYYMMDD-XXXX (e.g., TRX-20260205-A9K3)
     * Searchable via LIKE query in history endpoint.
     */
    private function generateTransactionCode(): string
    {
        do {
            $date = now()->format('Ymd');
            $random = Str::upper(Str::random(4));
            $code = "TRX-{$date}-{$random}";
        } while (Transaction::where('transaction_code', $code)->exists());

        return $code;
    }

    /**
     * Stateless Transaction Creation
     * Supports POST /api/transactions with direct items payload
     */
    public function createTransaction(int $userId, array $items, float $paymentAmount): Transaction
    {
        return DB::transaction(function () use ($userId, $items, $paymentAmount) {
            $totalAmount = 0;
            $transactionItems = [];

            // 1. Merge duplicates
            $mergedItems = [];
            foreach ($items as $item) {
                $id = $item['product_id'];
                if (!isset($mergedItems[$id])) {
                    $mergedItems[$id] = ['product_id' => $id, 'qty' => 0];
                }
                $mergedItems[$id]['qty'] += $item['qty'];
            }

            // 2. Validate & Lock Products, Calculate Total
            foreach ($mergedItems as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                // Manual check removed as findOrFail throws ModelNotFoundException

                if ($product->stock < $item['qty']) {
                    throw new BusinessException("Insufficient stock for {$product->product_name}");
                }

                $subtotal = $product->price * $item['qty'];
                $totalAmount += $subtotal;

                $transactionItems[] = [
                    'product_model' => $product, // store model for later
                    'quantity'      => $item['qty'],
                    'price'         => $product->price,
                    'subtotal'      => $subtotal,
                ];
            }

            // 2. Validate Payment
            if ($paymentAmount < $totalAmount) {
                throw new BusinessException("Payment amount insufficient. Total: {$totalAmount}, Paid: {$paymentAmount}");
            }

            // 3. Create Transaction
            $transaction = Transaction::create([
                'transaction_code' => $this->generateTransactionCode(),
                'transaction_date' => now(),
                'total_amount'     => $totalAmount,
                'payment_amount'   => $paymentAmount,
                'change_amount'    => $paymentAmount - $totalAmount,
                'status'           => Transaction::STATUS_COMPLETED,
                'user_id'          => $userId,
            ]);

            // 4. Create Details & Update Stock
            foreach ($transactionItems as $item) {
                TransactionDetail::create([
                    'transaction_id'       => $transaction->id,
                    'product_id'           => $item['product_model']->id,
                    'quantity'             => $item['quantity'],
                    'price_at_transaction' => $item['price'],
                    'subtotal'             => $item['subtotal'],
                ]);

                // Decrement Stock
                $item['product_model']->decrement('stock', $item['quantity']);
            }

            return $transaction;
        });
    }
}
