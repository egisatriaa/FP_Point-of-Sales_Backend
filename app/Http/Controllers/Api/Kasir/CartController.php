<?php

namespace App\Http\Controllers\Api\Kasir;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Http\Requests\Cart\StoreCartRequest;
use App\Models\Cart;
use App\Models\Product;
use App\Traits\ApiResponse;

/**
 * Cart Controller
 */

class CartController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/cart",
     *     summary="Get cart items",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart items retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="cart_id", type="integer"),
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="product_name", type="string"),
     *                     @OA\Property(property="price", type="number"),
     *                     @OA\Property(property="quantity", type="integer"),
     *                     @OA\Property(property="subtotal", type="number")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $cart = Cart::with('product')
            ->where('user_id', request()->user()->id)
            ->get()
            ->map(fn($item) => [
                'cart_id'      => $item->id,
                'product_id'   => $item->product_id,
                'product_name' => $item->product->product_name,
                'price'        => $item->product->price,
                'quantity'     => $item->quantity,
                'subtotal'     => $item->product->price * $item->quantity,
            ]);

        return $this->success($cart);
    }

    /**
     * @OA\Post(
     *     path="/cart",
     *     summary="Add product to cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Product to add to cart",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"product_id", "quantity"},
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="quantity", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cart updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cart updated"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="cart_id", type="integer", example=1),
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="product_name", type="string", example="Product 1"),
     *                 @OA\Property(property="price", type="number", example=10000),
     *                 @OA\Property(property="quantity", type="integer", example=2),
     *                 @OA\Property(property="subtotal", type="number", example=20000)
     *             )
     *         )
     *     )
     * )
     */
    public function store(StoreCartRequest $request)
    {
        $user = $request->user();
        $product = Product::findOrFail($request->product_id);

        if ($product->stock < $request->quantity) {
            return $this->error('Stock not sufficient', 422);
        }

        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id, 'product_id' => $product->id],
            ['quantity' => 0]
        );

        if ($product->stock < ($cart->quantity + $request->quantity)) {
            return $this->error('Stock not sufficient for total quantity', 422);
        }

        $cart->increment('quantity', $request->quantity);

        return $this->success($cart, 'Cart updated', 201);
    }

    /**
     * @OA\Delete(
     *     path="/cart/{id}",
     *     summary="Remove item from cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(int $id)
    {
        Cart::where('id', $id)
            ->where('user_id', request()->user()->id)
            ->delete();

        return $this->success(null, 'Item removed');
    }

    /**
     * @OA\Delete(
     *     path="/cart",
     *     summary="Clear cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function clear()
    {
        Cart::where('user_id', request()->user()->id)->delete();

        return $this->success(null, 'Cart cleared');
    }
}
