<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use Laravel\Sanctum\Sanctum;

class ApiFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true; // Run DatabaseSeeder automatically

    public function test_login_api_success()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'cashier@pos.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_id',
                    'token'
                ]
            ]);
    }

    public function test_checkout_api_success()
    {
        // 1. Login as Cashier (to get the user)
        $user = User::where('email', 'cashier@pos.com')->first();

        // 2. Add Item to Cart (Manual simulation since Cart API doesn't exist yet)
        $product = Product::first();
        Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // 3. Act: Call Checkout API
        // We use ActingAs or pass Bearer token.
        // Let's use Sanctum::actingAs for easier testing
        Sanctum::actingAs($user, ['access-api']);

        $paymentAmount = ($product->price * 2) + 10000; // Pay more to get change

        $response = $this->postJson('/api/checkout', [
            'payment_amount' => $paymentAmount
        ]);

        // 4. Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Transaction completed',
            ]);

        // Check if Stock Reduced
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 100 - 2, // Initial 100, bought 2
        ]);

        // Check if Cart Emptied
        $this->assertDatabaseMissing('carts', [
            'user_id' => $user->id,
        ]);

        // Check Transaction Created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
    }
}
