<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProductApiTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminAndToken(): string
    {
        $role = Role::firstOrCreate(['role_name' => 'admin']);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@product.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'is_deleted' => false,
        ]);

        return $admin->createToken('admin-token')->plainTextToken;
    }

    /** @test */
    public function admin_can_create_product()
    {
        $token = $this->createAdminAndToken();

        $category = Category::create([
            'category_name' => 'Food',
        ]);

        $payload = [
            'category_id'  => $category->id,
            'sku'          => 'SKU-001',
            'product_name' => 'Burger',
            'description'  => 'Test product',
            'price'        => 50000,
            'stock'        => 10,
        ];

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->postJson('/api/admin/products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sku', 'SKU-001')
            ->assertJsonPath('data.product_name', 'Burger');

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-001',
            'product_name' => 'Burger',
        ]);
    }

    /** @test */
    public function admin_can_update_product()
    {
        $token = $this->createAdminAndToken();

        $category = Category::create([
            'category_name' => 'Food',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-002',
            'product_name' => 'Fries',
            'description' => 'Old desc',
            'price' => 20000,
            'stock' => 5,
        ]);

        $payload = [
            'category_id'  => $category->id,
            'sku'          => 'SKU-002',
            'product_name' => 'French Fries',
            'description'  => 'Updated desc',
            'price'        => 25000,
            'stock'        => 15,
        ];

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->putJson("/api/admin/products/{$product->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product_name', 'French Fries')
            ->assertJsonPath('data.price', 25000);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'product_name' => 'French Fries',
            'price' => 25000,
        ]);
    }

    /** @test */
    public function admin_cannot_delete_product_with_transaction_history()
    {
        $token = $this->createAdminAndToken();

        $category = Category::create([
            'category_name' => 'Food',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-003',
            'product_name' => 'Pizza',
            'description' => 'Pizza test',
            'price' => 80000,
            'stock' => 20,
        ]);

        $transaction = Transaction::create([
            'transaction_code' => 'TRX-DEL-001',
            'transaction_date' => now(),
            'total_amount' => 80000,
            'payment_amount' => 80000,
            'change_amount' => 0,
            'status' => 'completed',
            'user_id' => User::first()->id,
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price_at_transaction' => 80000,
            'subtotal' => 80000,
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->deleteJson("/api/admin/products/{$product->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }
}
