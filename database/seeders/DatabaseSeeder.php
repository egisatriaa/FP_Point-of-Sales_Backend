<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Roles
        $adminRole = Role::create(['role_name' => 'admin']);
        $cashierRole = Role::create(['role_name' => 'cashier']);

        // 2. Create Users
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@pos.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@pos.com',
            'password' => Hash::make('password'),
            'role_id' => $cashierRole->id,
            'is_active' => true,
        ]);

        // 3. Create Categories
        $foodCat = Category::create(['category_name' => 'Food']);
        $drinkCat = Category::create(['category_name' => 'Drink']);

        // 4. Create Products
        Product::create([
            'category_id' => $foodCat->id,
            'sku' => 'FOOD-001',
            'product_name' => 'Nasi Goreng',
            'description' => 'Nasi Goreng Spesial',
            'price' => 25000,
            'stock' => 100,
        ]);

        Product::create([
            'category_id' => $drinkCat->id,
            'sku' => 'DRINK-001',
            'product_name' => 'Es Teh Manis',
            'description' => 'Es Teh Segar',
            'price' => 5000,
            'stock' => 50,
        ]);
    }
}
