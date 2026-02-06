<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;
    // Constant Status
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'user_id',
        'status',
    ];

    /* ================== RELATION ================== */

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Cart Items (Detail)
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Accessor: Total Quantity
     */
    public function getTotalQuantityAttribute()
    {
        return $this->items->sum('quantity');
    }

    /**
     * Accessor: Total Amount
     */
    public function getTotalAmountAttribute()
    {
        // Sum of (price * quantity) for all items
        return $this->items->sum(fn($item) => $item->price * $item->quantity);
    }

    /* ================== SCOPES ================== */

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
