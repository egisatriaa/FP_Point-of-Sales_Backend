<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Product;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

use App\Services\ImageUploadService;

/**
 * Product Controller (Admin)
 */

class ProductController extends Controller
{
    public function __construct(
        protected ImageUploadService $imageUploadService
    ) {}

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
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category');

        // Filter by Category (Scalable via SKU Prefix)
        if ($request->has('category') && $request->category !== 'all') {
            $category = strtoupper($request->category);
            // Handle plural/singular mapping like tools -> TOOL
            if ($category === 'TOOLS') $category = 'TOOL';
            
            $query->where('sku', 'LIKE', $category . '-%');
        }

        // Filter by Search (Name or SKU)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('id', 'desc')->get(),
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
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"category_id", "sku", "product_name", "price", "stock"},
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="sku", type="string", example="PROD-001"),
     *                 @OA\Property(property="product_name", type="string", example="Product 1"),
     *                 @OA\Property(property="description", type="string", example="Description 1"),
     *                 @OA\Property(property="price", type="number", format="float", example=10000),
     *                 @OA\Property(property="stock", type="integer", example=10),
     *                 @OA\Property(property="image", type="string", format="binary")
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
            'image'         => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            $path = $this->imageUploadService->upload($request->file('image'));
            $validated['img_product'] = $path; // Map to correct DB column
        }

        unset($validated['image']); // Remove 'image' from array as DB column is 'img_product'

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
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"category_id", "sku", "product_name", "price", "stock", "_method"},
     *                 @OA\Property(property="_method", type="string", example="PUT"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="sku", type="string", example="PROD-001"),
     *                 @OA\Property(property="product_name", type="string", example="Product Updated"),
     *                 @OA\Property(property="description", type="string", example="Description Updated"),
     *                 @OA\Property(property="price", type="number", format="float", example=15000),
     *                 @OA\Property(property="stock", type="integer", example=20),
     *                 @OA\Property(property="image", type="string", format="binary")
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
            'image'         => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            // Upload new image and delete old one if exists
            $path = $this->imageUploadService->upload(
                $request->file('image'),
                'products',
                $product->img_product
            );
            $validated['img_product'] = $path;
        }

        unset($validated['image']);

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

        // Delete image from storage
        $this->imageUploadService->delete($product->img_product);

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted',
        ]);
    }
}
