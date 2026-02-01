<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Product",
 *     title="Product",
 *     description="Product model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="sku", type="string", example="PROD-001"),
 *     @OA\Property(property="product_name", type="string", example="Sample Product"),
 *     @OA\Property(property="description", type="string", example="Product description"),
 *     @OA\Property(property="price", type="number", format="float", example=10000),
 *     @OA\Property(property="stock", type="integer", example=50),
 *     @OA\Property(property="image", type="string", nullable=true, example="products/image.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Product extends Model
{
    protected $fillable = [
        'category_id',
        'sku',
        'product_name',
        'description',
        'price',
        'stock',
    ];

    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
    ];

    /* ================== RELATION ================== */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    /* ================== ACCESSOR ================== */

    public function getIsAvailableAttribute()
    {
        return $this->stock > 0;
    }
}
