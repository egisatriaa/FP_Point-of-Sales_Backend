<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\Transaction;
use Laravel\Sanctum\Sanctum;

class CashierFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $cashier;
    protected $cashierRole;
    protected $product;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Roles
        $this->cashierRole = Role::firstOrCreate(['id' => 2], ['role_name' => 'cashier']);
        Role::firstOrCreate(['id' => 1], ['role_name' => 'admin']);

        // Create Cashier User
        $this->cashier = User::factory()->create([
            'role_id' => $this->cashierRole->id
        ]);
        
        // Create Product (will auto-create generic Category)
        $this->product = Product::factory()->create();
    }

    public function test_cashier_can_view_cart()
    {
        Sanctum::actingAs($this->cashier, ['*']);

        $response = $this->getJson('/api/cart');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_cashier_can_add_to_cart()
    {
        Sanctum::actingAs($this->cashier, ['*']);

        $response = $this->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'quantity' => 2
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.quantity', 2);
    }

    public function test_cashier_can_create_transaction()
    {
        Sanctum::actingAs($this->cashier, ['*']);

        // Assuming direct transaction creation (stateless)
        $response = $this->postJson('/api/transactions', [
            'items' => [
                ['product_id' => $this->product->id, 'qty' => 1]
            ],
            'payment_amount' => 100000
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);
    }

    public function test_cashier_can_view_my_transactions()
    {
        Sanctum::actingAs($this->cashier, ['*']);

        // Create a dummy transaction first
        Transaction::factory()->create([
            'user_id' => $this->cashier->id,
            'transaction_code' => 'TRX-TEST-001'
        ]);

        $response = $this->getJson('/api/transactions/my');

        $response->assertStatus(200)
                 ->assertJsonFragment(['transaction_code' => 'TRX-TEST-001']);
    }

    public function test_cashier_dashboard_stats_access()
    {
        Sanctum::actingAs($this->cashier, ['*']);

        $response = $this->getJson('/api/cashier/dashboard/stats');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_cashier_cannot_access_admin_dashboard()
    {
        Sanctum::actingAs($this->cashier, ['*']);

        $response = $this->getJson('/api/admin/dashboard/stats');

        // Expect Forbidden (403) or Unauthorized depending on middleware
        // Since we use middleware role:admin, it should be 403
        $response->assertStatus(403);
    }
}
