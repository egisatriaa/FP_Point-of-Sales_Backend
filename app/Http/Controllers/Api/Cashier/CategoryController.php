<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Cashier Categories",
 *     description="Category viewing for Cashier"
 * )
 */
class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/cashier/categories",
     *     tags={"Cashier Categories"},
     *     summary="List all categories",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Category"))
     *         )
     *     )
     * )
     */
    public function index()
    {
        // Cashiers typically need all categories to find products
        $categories = Category::all();
        return $this->success($categories);
    }
}
