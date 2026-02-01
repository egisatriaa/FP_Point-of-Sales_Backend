<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
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
