<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price', // Snapshot price
    ];

    /**
     * Relasi ke Cart (Header)
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Relasi ke Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor untuk Subtotal
     */
    public function getSubtotalAttribute()
    {
        // Gunakan price snapshot jika ada, atau fallback ke product price (opsional logic)
        // Di sini kita strict pakai snapshot price di DB
        return $this->price * $this->quantity;
    }
}
