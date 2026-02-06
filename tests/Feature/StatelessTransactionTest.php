<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatelessTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected $cashier;
    protected $products;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Roles
        Role::create(['role_name' => 'admin']);
        $cashierRole = Role::create(['role_name' => 'cashier']);

        // Create Cashier
        $this->cashier = User::factory()->create([
            'role_id' => $cashierRole->id,
            'password' => bcrypt('password'),
        ]);

        // Create Category
        if (\App\Models\Category::count() === 0) {
             \App\Models\Category::create(['category_name' => 'Food']);
        }

        // Create Products
        $this->products = Product::factory()->count(3)->create([
            'stock' => 10,
            'price' => 10000,
            'category_id' => 1, 
        ]);
        
    }

    /** @test */
    public function success_checkout()
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/transactions', [
                'items' => [
                    ['product_id' => $this->products[0]->id, 'qty' => 2],
                    ['product_id' => $this->products[1]->id, 'qty' => 1],
                ],
                'paid_amount' => 50000,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'transaction_id',
                    'transaction_code',
                    'total_amount',
                    'change_amount',
                ]
            ]);

        // Verify DB
        $this->assertDatabaseHas('transactions', [
            'total_amount' => 30000, // (2*10000) + (1*10000)
            'payment_amount' => 50000,
            'change_amount' => 20000,
            'user_id' => $this->cashier->id,
        ]);

        // Verify Stock Deduced
        $this->assertDatabaseHas('products', ['id' => $this->products[0]->id, 'stock' => 8]);
        $this->assertDatabaseHas('products', ['id' => $this->products[1]->id, 'stock' => 9]);
    }

    /** @test */
    public function insufficient_stock()
    {
        // Set stock to 1
        $this->products[0]->update(['stock' => 1]);

        $response = $this->actingAs($this->cashier)
            ->postJson('/api/transactions', [
                'items' => [
                    ['product_id' => $this->products[0]->id, 'qty' => 2],
                ],
                'paid_amount' => 50000,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]); // Expecting BusinessException handled as 422
            
        // Stock should remain 1
        $this->assertDatabaseHas('products', ['id' => $this->products[0]->id, 'stock' => 1]);
    }

    /** @test */
    public function duplicate_items_merged_correctly()
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/transactions', [
                'items' => [
                    ['product_id' => $this->products[0]->id, 'qty' => 2],
                    ['product_id' => $this->products[0]->id, 'qty' => 3], // Same product
                ],
                'paid_amount' => 60000,
            ]);

        $response->assertStatus(201);

        // Required Stock: 2 + 3 = 5. Price: 50000.
        $this->assertDatabaseHas('transactions', [
            'total_amount' => 50000,
        ]);

        // Verify Stock Deduced (10 - 5 = 5)
        $this->assertDatabaseHas('products', ['id' => $this->products[0]->id, 'stock' => 5]);
    }

    /** @test */
    public function payment_less_than_total()
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/transactions', [
                'items' => [
                    ['product_id' => $this->products[0]->id, 'qty' => 2], // 20000
                ],
                'paid_amount' => 10000, // Insufficient
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false]);
    }

    /** @test */
    public function empty_items_array_rejected()
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/transactions', [
                'items' => [],
                'paid_amount' => 50000,
            ]);

        $response->assertStatus(422); // Validation error
    }

    /** @test */
    public function concurrent_checkout_stock_protection_simulation()
    {
        // Note: Real concurrency testing in PHPUnit is hard. 
        // We simulate the condition that *would* cause race condition if lock missing 
        // but here we primarily verify the logic executes atomically.
        // We will try to buy more than stock across two "transactions" sequentially 
        // to ensure generic logic holds, but for lockForUpdate we trust the code review.
        
        $this->products[0]->update(['stock' => 5]);
        
        // Transaction 1: Buy 3 (Success)
        $response1 = $this->actingAs($this->cashier)
            ->postJson('/api/transactions', [
                'items' => [['product_id' => $this->products[0]->id, 'qty' => 3]],
                'paid_amount' => 50000,
            ]);
        $response1->assertStatus(201);
        
        // Transaction 2: Buy 3 (Fail: Stock is now 2)
        $response2 = $this->actingAs($this->cashier)
            ->postJson('/api/transactions', [
                'items' => [['product_id' => $this->products[0]->id, 'qty' => 3]],
                'paid_amount' => 50000,
            ]);
        $response2->assertStatus(422);
        
        $this->assertDatabaseHas('products', ['id' => $this->products[0]->id, 'stock' => 2]);
    }
}
