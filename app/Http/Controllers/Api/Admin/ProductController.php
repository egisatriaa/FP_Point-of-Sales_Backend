<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * List products (Admin)
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Product::with('category')->orderBy('id')->get(),
        ]);
    }

    /**
     * Store new product
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id'   => ['required', 'exists:categories,id'],
            'sku'           => ['required', 'string', 'max:50', 'unique:products,sku'],
            'product_name'  => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'price'         => ['required', 'numeric', 'min:0'],
            'stock'         => ['required', 'integer', 'min:0'],
        ]);

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product created',
            'data' => $product,
        ], 201);
    }

    /**
     * Show product detail
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with('category')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update product
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id'   => ['required', 'exists:categories,id'],
            'sku'           => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'product_name'  => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'price'         => ['required', 'numeric', 'min:0'],
            'stock'         => ['required', 'integer', 'min:0'],
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated',
            'data' => $product,
        ]);
    }

    /**
     * Delete product (safe)
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $usedInTransaction = TransactionDetail::where('product_id', $product->id)->exists();

        if ($usedInTransaction) {
            return response()->json([
                'success' => false,
                'message' => 'Product cannot be deleted because it has transaction history',
            ], 422);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted',
        ]);
    }

    public function available(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Product::where('stock', '>', 0)
                ->orderBy('product_name')
                ->get(),
        ]);
    }
}
