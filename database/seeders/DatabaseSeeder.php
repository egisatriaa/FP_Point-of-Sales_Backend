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
        $adminRole = Role::firstOrCreate(['role_name' => 'admin']);
        $cashierRole = Role::firstOrCreate(['role_name' => 'cashier']);

        // 2. Create Users
        User::firstOrCreate(
            ['email' => 'admin@pos.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'cashier@pos.com'],
            [
                'name' => 'Cashier User',
                'password' => Hash::make('password'),
                'role_id' => $cashierRole->id,
                'is_active' => true,
            ]
        );

        // 3. Create Categories
        $foodCat = Category::firstOrCreate(['category_name' => 'Food']);
        $drinkCat = Category::firstOrCreate(['category_name' => 'Drink']);
        $toolsCat = Category::firstOrCreate(['category_name' => 'Tools']);

        // 4. Create Products
        Product::firstOrCreate(
            ['sku' => 'FOOD-001'],
            [
                'category_id' => $foodCat->id,
                'product_name' => 'Nasi Goreng',
                'description' => 'Nasi Goreng Spesial',
                'price' => 25000,
                'stock' => 100,
            ]
        );

        Product::firstOrCreate(
            ['sku' => 'DRINK-001'],
            [
                'category_id' => $drinkCat->id,
                'product_name' => 'Es Teh Manis',
                'description' => 'Es Teh Segar',
                'price' => 5000,
                'stock' => 50,
            ]
        );

        // Additional Food Products
        Product::firstOrCreate(
            ['sku' => 'FOOD-002'],
            [
                'category_id' => $foodCat->id,
                'product_name' => 'Mie Goreng',
                'description' => 'Mie Goreng Spesial Telur',
                'price' => 18000,
                'stock' => 80,
            ]
        );

        Product::firstOrCreate(
            ['sku' => 'FOOD-003'],
            [
                'category_id' => $foodCat->id,
                'product_name' => 'Sate Ayam',
                'description' => 'Sate Ayam Madura 10 Tusuk',
                'price' => 30000,
                'stock' => 60,
            ]
        );

        // Additional Drink Products
        Product::firstOrCreate(
            ['sku' => 'DRINK-002'],
            [
                'category_id' => $drinkCat->id,
                'product_name' => 'Kopi Susu Gula Aren',
                'description' => 'Kopi Susu Kekinian',
                'price' => 15000,
                'stock' => 100,
            ]
        );

        Product::firstOrCreate(
            ['sku' => 'DRINK-003'],
            [
                'category_id' => $drinkCat->id,
                'product_name' => 'Jus Jeruk Murni',
                'description' => 'Jus Jeruk Tanpa Gula',
                'price' => 12000,
                'stock' => 40,
            ]
        );

        // Tools Products
        Product::firstOrCreate(
            ['sku' => 'TOOL-001'],
            [
                'category_id' => $toolsCat->id,
                'product_name' => 'Sedotan Stainless',
                'description' => 'Sedotan Ramah Lingkungan',
                'price' => 5000,
                'stock' => 200,
            ]
        );

        Product::firstOrCreate(
            ['sku' => 'TOOL-002'],
            [
                'category_id' => $toolsCat->id,
                'product_name' => 'Tote Bag Canvas',
                'description' => 'Tas Belanja Ramah Lingkungan',
                'price' => 25000,
                'stock' => 4,
            ]
        );
    }
}
