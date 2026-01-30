<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
        'is_deleted',
        'profile_img',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected static function booted()
    {
        static::addGlobalScope('active', function ($query) {
            $query->where('is_active', true)
                ->where('is_deleted', false);
        });
    }

    /* ================== RELATION ================== */

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    /* ================== QUERY SCOPE ================== */

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('is_deleted', false);
    }
}
