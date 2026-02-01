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
    /**
     * @OA\Get(
     *   path="/products",
     *   summary="Get all available products",
     *   description="Retrieve list of products with stock greater than zero",
     *   tags={"Products Public"},
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
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => Product::where('stock', '>', 0)
                ->orderBy('product_name')
                ->get(['id', 'product_name', 'price', 'stock']),
        ]);
    }
}
