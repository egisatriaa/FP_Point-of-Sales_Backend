<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
