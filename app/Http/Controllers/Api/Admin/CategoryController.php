<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Admin Categories",
 *     description="Category management for Admin"
 * )
 */
class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/admin/categories",
     *     tags={"Admin Categories"},
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
        $categories = Category::all();
        return $this->success($categories);
    }

    /**
     * @OA\Post(
     *     path="/admin/categories",
     *     tags={"Admin Categories"},
     *     summary="Create a new category",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_name"},
     *             @OA\Property(property="category_name", type="string", example="Food")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|unique:categories,category_name',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $category = Category::create($request->all());

        return $this->success($category, 'Category created successfully', 201);
    }

    /**
     * @OA\Get(
     *     path="/admin/categories/{id}",
     *     tags={"Admin Categories"},
     *     summary="Get category by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found"
     *     )
     * )
     */
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        return $this->success($category);
    }

    /**
     * @OA\Put(
     *     path="/admin/categories/{id}",
     *     tags={"Admin Categories"},
     *     summary="Update a category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_name"},
     *             @OA\Property(property="category_name", type="string", example="Updated/Food")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|unique:categories,category_name,' . $id,
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $category->update($request->all());

        return $this->success($category, 'Category updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/admin/categories/{id}",
     *     tags={"Admin Categories"},
     *     summary="Delete a category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete category with associated products"
     *     )
     * )
     */
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        // Check if category has products
        if ($category->products()->count() > 0) {
            return $this->error('Cannot delete category because it has related products.', 422);
        }

        $category->delete();

        return $this->success(null, 'Category deleted successfully');
    }
}
