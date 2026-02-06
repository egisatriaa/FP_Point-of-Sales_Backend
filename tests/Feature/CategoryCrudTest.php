<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use Laravel\Sanctum\Sanctum;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        
        $adminRole = Role::firstOrCreate(['role_name' => 'admin']);
        $cashierRole = Role::firstOrCreate(['role_name' => 'cashier']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->cashier = User::factory()->create(['role_id' => $cashierRole->id]);
    }

    public function test_admin_can_crud_categories()
    {
        Sanctum::actingAs($this->admin, ['*']);

        // 1. Create
        $response = $this->postJson('/api/admin/categories', [
            'category_name' => 'New Category'
        ]);
        $response->assertStatus(201)
                 ->assertJsonPath('data.category_name', 'New Category');
        
        $id = $response->json('data.id');

        // 2. Read
        $this->getJson("/api/admin/categories/$id")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $id);

        // 3. Update
        $this->putJson("/api/admin/categories/$id", [
            'category_name' => 'Updated Category'
        ])->assertStatus(200)
          ->assertJsonPath('data.category_name', 'Updated Category');

        // 4. Index
        $this->getJson('/api/admin/categories')
             ->assertStatus(200)
             ->assertJsonCount(1, 'data');

        // 5. Delete
        $this->deleteJson("/api/admin/categories/$id")
             ->assertStatus(200);
        
        $this->assertSoftDeleted('categories', ['id' => $id]);
    }

    public function test_cashier_can_read_categories_but_not_write()
    {
        Category::create(['category_name' => 'Existing Category']);

        Sanctum::actingAs($this->cashier, ['*']);

        // 1. Read Index (Allowed)
        // Note: Route is /api/cashier/categories
        $this->getJson('/api/cashier/categories')
             ->assertStatus(200)
             ->assertJsonCount(1, 'data');

        // 2. Write (Forbidden - Route doesn't exist or is protected)
        // Cashier has no route for POST /api/cashier/categories, and cannot access /api/admin/*
        
        $this->postJson('/api/admin/categories', ['category_name' => 'Hacker Cat'])
             ->assertStatus(403); // Forbidden by role:admin middleware
    }

    public function test_category_unique_validation()
    {
        Category::create(['category_name' => 'Unique Cat']);
        Sanctum::actingAs($this->admin, ['*']);

        $this->postJson('/api/admin/categories', ['category_name' => 'Unique Cat'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['category_name']);
    }
}
