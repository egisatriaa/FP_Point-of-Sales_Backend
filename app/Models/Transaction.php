<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     title="Transaction",
 *     description="Transaction model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="transaction_code", type="string", example="TRX-123456"),
 *     @OA\Property(property="transaction_date", type="string", format="date-time"),
 *     @OA\Property(property="total_amount", type="number", format="float", example=50000),
 *     @OA\Property(property="payment_amount", type="number", format="float", example=50000),
 *     @OA\Property(property="change_amount", type="number", format="float", example=0),
 *     @OA\Property(property="status", type="string", example="completed"),
 *     @OA\Property(property="user_id", type="integer", example=1)
 * )
 */
class Transaction extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $casts = [
        'transaction_date' => 'datetime',
    ];
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'transaction_code',
        'transaction_date',
        'total_amount',
        'payment_amount',
        'change_amount',
        'payment_method',
        'status',
        'user_id',
    ];

    /* ================== RELATION ================== */

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
