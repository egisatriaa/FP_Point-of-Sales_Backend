<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TransactionDetail",
 *     title="Transaction Detail",
 *     description="Item in a transaction",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="transaction_id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=5),
 *     @OA\Property(property="quantity", type="integer", example=2),
 *     @OA\Property(property="price_at_transaction", type="number", format="float", example=18000),
 *     @OA\Property(property="subtotal", type="number", format="float", example=36000)
 * )
 */
class TransactionDetail extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity',
        'price_at_transaction',
        'subtotal',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
