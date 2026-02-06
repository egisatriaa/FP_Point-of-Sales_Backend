<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Http\Requests\Cart\StoreCartRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Traits\ApiResponse;

/**
 * Cart Controller (Refactored: Header-Detail)
 */
class CartController extends Controller
{
    use ApiResponse;

    /**
     * Helper: Build consistent cart response structure
     * 
     * @param Cart|null $cart
     * @return array
     */
    private function buildCartResponse(?Cart $cart): array
    {
        if (!$cart) {
            return [
                'cart_id'        => null,
                'status'         => 'empty',
                'items'          => [],
                'total_quantity' => 0,
                'total_amount'   => 0,
            ];
        }

        // Ensure Items are loaded
        if (!$cart->relationLoaded('items')) {
            $cart->load(['items.product']);
        }

        return [
            'cart_id'        => $cart->id,
            'status'         => $cart->status,
            'total_quantity' => (int) $cart->total_quantity,
            'total_amount'   => (float) $cart->total_amount,
            'items'          => $cart->items->map(fn($item) => [
                'cart_item_id'   => $item->id,
                'product_id'     => $item->product_id,
                'product_name'   => $item->product->product_name ?? 'Unknown Product',
                'price_snapshot' => (float) $item->price,
                'quantity'       => (int) $item->quantity,
                'subtotal'       => (float) ($item->price * $item->quantity),
            ]),
        ];
    }

    /**
     * @OA\Get(
     *     path="/cart",
     *     summary="Get active cart items",
     *     description="Mengambil data cart aktif beserta item produk di dalamnya.",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="cart_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="total_quantity", type="integer", example=5),
     *                 @OA\Property(property="total_amount", type="number", example=150000),
     *                 @OA\Property(property="items", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="cart_item_id", type="integer", example=10),
     *                         @OA\Property(property="product_id", type="integer", example=1),
     *                         @OA\Property(property="product_name", type="string", example="Kopi Susu"),
     *                         @OA\Property(property="price_snapshot", type="number", example=15000),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="subtotal", type="number", example=30000)
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        // 1. Ambil cart aktif milik user login
        $cart = Cart::with(['items.product'])
            ->active()
            ->where('user_id', request()->user()->id)
            ->first();

        // 2. Gunakan helper untuk format response
        $data = $this->buildCartResponse($cart);

        return $this->success($data);
    }

    /**
     * @OA\Post(
     *     path="/cart",
     *     summary="Add product to cart",
     *     description="Menambahkan produk ke cart aktif. Jika produk sudah ada, quantity akan bertambah (increment). Response berisi data cart LENGKAP terbaru.",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"product_id", "quantity"},
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="quantity", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product added to cart"),
     *             @OA\Property(property="data", type="object",
     *                 description="Full Cart Object (Same as GET /cart)",
     *                 @OA\Property(property="cart_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="total_quantity", type="integer", example=5),
     *                 @OA\Property(property="total_amount", type="number", example=150000),
     *                 @OA\Property(property="items", type="array", @OA\Items())
     *             )
     *         )
     *     )
     * )
     */
    public function store(StoreCartRequest $request)
    {
        $user = $request->user();
        $productId = $request->product_id;
        $qtyToAdd  = $request->quantity;

        // 1. Ambil Data Produk untuk cek stock & harga
        $product = Product::findOrFail($productId);

        // 2. Cari atau Buat Cart Aktif (Header)
        $cart = Cart::firstOrCreate(
            [
                'user_id' => $user->id,
                'status'  => Cart::STATUS_ACTIVE
            ]
        );

        // 3. Cek apakah produk sudah ada di cart ini (Detail)
        $cartItem = $cart->items()->where('product_id', $productId)->first();

        // 4. Hitung total quantity nanti (existing + new)
        $currentQty = $cartItem ? $cartItem->quantity : 0;
        $newTotalQty = $currentQty + $qtyToAdd;

        // 5. Validasi Stock (WAJIB: berdasarkan total quantity di cart)
        if ($product->stock < $newTotalQty) {
            return $this->error("Stock tidak cukup. Tersedia: {$product->stock}, Di cart: {$currentQty}", 422);
        }

        // 6. Update atau Create Item
        if ($cartItem) {
            // Increment quantity jika sudah ada
            $cartItem->quantity = $newTotalQty;
            $cartItem->save();
        } else {
            // Buat item baru
            $cart->items()->create([
                'product_id' => $productId,
                'quantity'   => $qtyToAdd,
                'price'      => $product->price, // Simpan Price Snapshot
            ]);
        }
        
        // 7. Refresh cart agar data items dan total terbaru ter-load
        // Kita perlu load items.product untuk formatting
        $cart->load(['items.product']);

        // 8. Return response lengkap menggunakan helper yang sama dengan index()
        $data = $this->buildCartResponse($cart);

        return $this->success($data, 'Product added to cart');
    }

    /**
     * @OA\Delete(
     *     path="/cart/{id}",
     *     summary="Remove item from cart",
     *     description="Menghapus spesifik item dari cart berdasarkan cart_item_id.",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID dari Cart Item (bukan Product ID)",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item removed successfully"
     *     )
     * )
     */
    public function destroy(int $id)
    {
        $user = request()->user();

        // 1. Cari Item yang valid (milik cart aktif user tersebut)
        $item = CartItem::where('id', $id)
            ->whereHas('cart', function ($query) use ($user) {
                $query->active()->where('user_id', $user->id);
            })
            ->first();

        if (!$item) {
            return $this->error('Item not found or access denied', 404);
        }

        // 2. Hapus Item
        $item->delete();

        // 3. (Opsional) Jika cart kosong, cart header bisa dihapus atau dibiarkan.
        // Sesuai instruksi: Header-Detail. Biarkan header tetap ada atau tidak masalah.
        
        return $this->success(null, 'Item removed from cart');
    }

    /**
     * @OA\Delete(
     *     path="/cart",
     *     summary="Clear cart",
     *     description="Menghapus semua item di cart aktif user.",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart cleared successfully"
     *     )
     * )
     */
    public function clear()
    {
        $user = request()->user();

        // 1. Cari Cart Aktif
        $cart = Cart::active()->where('user_id', $user->id)->first();

        if ($cart) {
            // 2. Hapus semua items (Detail)
            $cart->items()->delete();
        }

        return $this->success(null, 'Cart cleared');
    }

}
