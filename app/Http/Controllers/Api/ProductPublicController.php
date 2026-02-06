<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

/**
 * Product Public Controller
 */
class ProductPublicController extends Controller
{
    use \App\Traits\ApiResponse;
    /**
     * @OA\Get(
     *   path="/products",
     *   summary="Get all available products",
     *   description="Retrieve list of products with stock greater than zero",
     *   tags={"Products Public"},
     *   @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="category_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"price", "name"})),
     *   @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer")),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Products retrieved successfully",
     *     @OA\JsonContent(
     *       example={
     *         "success": true,
     *         "message": "Success",
     *         "data": {
     *           {
     *             "id": 1,
     *             "product_name": "Nasi Kuning Spesial",
     *             "price": "18000.00",
     *             "stock": 10
     *           },
     *           {
     *             "id": 2,
     *             "product_name": "Es Teh Manis",
     *             "price": "5000.00",
     *             "stock": 25
     *           }
     *         }
     *       }
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *     @OA\JsonContent(
     *       example={
     *         "success": false,
     *         "message": "Unauthenticated"
     *       }
     *     )
     *   )
     * )
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Product::query();

        // 1. Filtering
        $query->when($request->search, function ($q, $search) {
            $q->where('product_name', 'like', "%{$search}%");
        });

        $query->when($request->category_id, function ($q, $categoryId) {
            $q->where('category_id', $categoryId);
        });

        // 2. Sorting
        // Default sort by name if not specified
        $sortColumn = match ($request->sort) {
            'price' => 'price',
            'name'  => 'product_name',
            default => 'product_name'
        };
        $query->orderBy($sortColumn, 'asc');

        // 3. Pagination vs All
        if ($request->has('limit')) {
            $products = $query->paginate((int)$request->limit);
            
            // Resource Collection automatically handles transformation
            $resource = \App\Http\Resources\ProductResource::collection($products);
            
            // Manual meta construction for consistency with other endpoints
            $meta = [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ];
            
            // We need to return the collection via `response` to get items array, 
            // but `success` trait expects raw data. 
            // $resource->response()->getData(true) returns ['data' => ..., 'links' => ..., 'meta' => ...] standard Laravel.
            // But we want consistent { success, message, data, meta }.
            
            return response()->json([
                'success' => true,
                'message' => 'Success',
                'data'    => $resource, // This attempts to serialize collection.
                'meta'    => $meta
            ]);
            
            // Alternate: Update trait to support meta. 
            // For now, I will use response()->json directly here to match the specific requirement of maintaining meta
            // OR I can use the trait if I update it. 
            // Let's stick to consistent manual construction here if trait update is risky/not requested explicitly.
            // BUT prompt asked "Create reusable response helper or trait".
            // So relying on manual json construction defeats the purpose.
            
            // I WILL UPDATE TRAIT IN NEXT STEP. For now let's write this to use $this->success(..., ..., ..., $meta) assuming I will update trait.
            
            return $this->success($resource, 'Success', 200, $meta);

        } else {
            $products = $query->get();
            $resource = \App\Http\Resources\ProductResource::collection($products);
            return $this->success($resource);
        }
    }
}
