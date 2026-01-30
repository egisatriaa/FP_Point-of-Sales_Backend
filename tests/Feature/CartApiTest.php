<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    private function createKasirAndToken(): string
    {
        $role = Role::create(['role_name' => 'cashier']);

        $kasir = User::create([
            'name' => 'Kasir Test',
            'email' => 'kasir@test.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'is_deleted' => false,
        ]);

        return $kasir->createToken('kasir-token')->plainTextToken;
    }

    private function createProduct(): Product
    {
        $category = Category::create([
            'category_name' => 'Food',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-CART-001',
            'product_name' => 'Burger',
            'description' => 'Cart test product',
            'price' => 50000,
            'stock' => 10,
        ]);
    }

    /** @test */
    public function kasir_can_add_product_to_cart()
    {
        $token = $this->createKasirAndToken();
        $product = $this->createProduct();

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('carts', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    /** @test */
    public function adding_same_product_increases_quantity()
    {
        $token = $this->createKasirAndToken();
        $product = $this->createProduct();

        $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->assertDatabaseHas('carts', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    /** @test */
    public function kasir_cannot_add_product_exceeding_stock()
    {
        $token = $this->createKasirAndToken();
        $product = $this->createProduct();

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function kasir_can_view_cart()
    {
        $token = $this->createKasirAndToken();
        $product = $this->createProduct();

        Cart::create([
            'user_id' => User::first()->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.product_name', 'Burger')
            ->assertJsonPath('data.0.quantity', 2)
            ->assertJsonPath('data.0.subtotal', 100000);
    }

    /** @test */
    public function kasir_can_remove_cart_item()
    {
        $token = $this->createKasirAndToken();
        $product = $this->createProduct();

        $cart = Cart::create([
            'user_id' => User::first()->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->deleteJson("/api/cart/{$cart->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('carts', [
            'id' => $cart->id,
        ]);
    }

    /** @test */
    public function kasir_can_clear_cart()
    {
        $token = $this->createKasirAndToken();
        $product = $this->createProduct();

        Cart::create([
            'user_id' => User::first()->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->deleteJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('carts', 0);
    }
}
