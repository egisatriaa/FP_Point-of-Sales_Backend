<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Product;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * Product Controller (Admin)
 */

class ProductController extends Controller
{
    /**
     * List products (Admin)
     * 
     * @OA\Get(
     *     path="/admin/products",
     *     summary="List all products",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#\/components\/schemas\/Product"))
     *         )
     *     )
     * )
     * 
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
     * 
     * @OA\Post(
     *     path="/admin/products",
     *     summary="Store new product",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Product data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"category_id", "sku", "product_name", "price", "stock"},
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="sku", type="string", example="PROD-001"),
     *                 @OA\Property(property="product_name", type="string", example="Product 1"),
     *                 @OA\Property(property="description", type="string", example="Description 1"),
     *                 @OA\Property(property="price", type="number", format="float", example=10000),
     *                 @OA\Property(property="stock", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product created"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     )
     * )
     * 
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
     * 
     * @OA\Get(
     *     path="/admin/products/{id}",
     *     summary="Show product detail",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#\/components\/schemas\/Product")
     *         )
     *     )
     * )
     * 
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
     * 
     * @OA\Put(
     *     path="/admin/products/{id}",
     *     summary="Update product",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Product data to update",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"category_id", "sku", "product_name", "price", "stock"},
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="sku", type="string", example="PROD-001"),
     *                 @OA\Property(property="product_name", type="string", example="Product Updated"),
     *                 @OA\Property(property="description", type="string", example="Description Updated"),
     *                 @OA\Property(property="price", type="number", format="float", example=15000),
     *                 @OA\Property(property="stock", type="integer", example=20)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product updated"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     )
     * )
     * 
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
     * 
     * @OA\Delete(
     *     path="/admin/products/{id}",
     *     summary="Delete product",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     * 
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
}
