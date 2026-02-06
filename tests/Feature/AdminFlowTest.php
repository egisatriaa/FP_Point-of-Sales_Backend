<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\Transaction;
use Laravel\Sanctum\Sanctum;

class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['id' => 1], ['role_name' => 'admin']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'cashier']);

        $this->admin = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'email' => 'admin@test.com'
        ]);
    }

    public function test_admin_can_access_dashboard_stats()
    {
        Sanctum::actingAs($this->admin, ['*']);
        
        $response = $this->getJson('/api/admin/dashboard/stats');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_all_transactions()
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Create a separate cashier for transactions to avoid Role collision
        $cashier = User::factory()->create(['role_id' => 2]);

        // Create some transactions
        Transaction::factory()->count(3)->create([
            'user_id' => $cashier->id
        ]);

        $response = $this->getJson('/api/admin/transactions');
        
        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data.data');
    }

    public function test_admin_can_manage_products()
    {
        Sanctum::actingAs($this->admin, ['*']);

        // List
        $this->getJson('/api/admin/products')->assertStatus(200);

        // Create
        $response = $this->postJson('/api/admin/products', [
            'product_name' => 'New Product',
            'sku' => 'NEW-001',
            'price' => 50000,
            'stock' => 10,
            'category_id' => \App\Models\Category::factory()->create()->id
        ]);
        
        $response->assertStatus(201);
    }

    public function test_admin_cannot_access_cashier_dashboard()
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Assuming cashier dashboard is protected by role:cashier middleware
        // If it returns 403, pass. 
        // Note: Sometimes middleware redirects or returns 200 with empty data if implementation varies.
        // Based on route group `prefix('cashier/dashboard')` inside `role:cashier`, admin should be blocked.
        $this->getJson('/api/cashier/dashboard/stats')->assertStatus(403);
    }
}
