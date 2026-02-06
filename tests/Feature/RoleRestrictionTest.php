<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleRestrictionTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $roleName): string
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        $user = User::create([
            'name' => ucfirst($roleName),
            'email' => $roleName . '@test.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'is_deleted' => false,
        ]);

        return $user->createToken('token')->plainTextToken;
    }

    private function createProduct(): Product
    {
        $category = Category::create([
            'category_name' => 'Food',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-ROLE-001',
            'product_name' => 'Role Test Product',
            'price' => 10000,
            'stock' => 10,
        ]);
    }

    /** @test */
    public function cashier_can_access_cart_api()
    {
        $token = $this->createUserWithRole('cashier');
        $product = $this->createProduct();

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function admin_cannot_access_cart_api()
    {
        $token = $this->createUserWithRole('admin');

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/cart');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_cart_api()
    {
        $response = $this->getJson('/api/cart');

        $response->assertStatus(401);
    }

    /** @test */
    public function cashier_can_access_checkout()
    {
        $token = $this->createUserWithRole('cashier');
        $product = $this->createProduct();

        // add cart item first
        $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->postJson('/api/checkout', [
            'payment_amount' => 20000,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function admin_cannot_access_checkout()
    {
        $token = $this->createUserWithRole('admin');

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->postJson('/api/checkout', [
            'payment_amount' => 10000,
        ]);

        $response->assertStatus(403);
    }
}
