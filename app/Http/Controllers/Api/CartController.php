<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartRequest;
use App\Models\Cart;
use App\Models\Product;
use App\Traits\ApiResponse;

class CartController extends Controller
{
    use ApiResponse;

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

    public function destroy(int $id)
    {
        Cart::where('id', $id)
            ->where('user_id', request()->user()->id)
            ->delete();

        return $this->success(null, 'Item removed');
    }

    public function clear()
    {
        Cart::where('user_id', request()->user()->id)->delete();

        return $this->success(null, 'Cart cleared');
    }
}
