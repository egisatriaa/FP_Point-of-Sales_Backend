<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    // We cannot use RefreshDatabase because we want to test against the real migration we just ran
    // But usually RefreshDatabase is safer. Given the user context, I should be careful.
    // However, since we just migrated the DB, it should be fine. 
    // Let's use clean up in the test instead of RefreshDatabase to avoid wiping user data if not intended.
    // Actually, RefreshDatabase wipes the DB. The user is in "mini project" mode, maybe local DB has data.
    // I will use DatabaseTransactions to wrap tests in transaction so it rolls back.

    // Use RefreshDatabase to migrate the in-memory SQLite database
    use RefreshDatabase;

    public function test_category_soft_delete()
    {
        $category = Category::create(['category_name' => 'Soft Delete Test Cat']);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);

        $category->delete();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);

        $this->assertNull(Category::find($category->id));
        $this->assertNotNull(Category::withTrashed()->find($category->id));

        $category->restore();
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
        $this->assertNotNull(Category::find($category->id));
    }

    public function test_product_soft_delete()
    {
        // Need a category first
        $category = Category::create(['category_name' => 'Prod Test Cat']);

        $product = Product::create([
            'product_name' => 'Test Product',
            'category_id' => $category->id,
            'sku' => 'TEST-SKU-' . rand(1000, 9999),
            'price' => 10000,
            'stock' => 10
        ]);

        $product->delete();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertNull(Product::find($product->id));

        $product->restore();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    public function test_user_soft_delete()
    {
        // Ensure role exists or CREATE a dummy one if empty (Assuming role table logic)
        // Let's check roles first. Assuming id 1 exists orcreate it.
        $role = Role::firstOrCreate(['role_name' => 'tester'], ['role_name' => 'tester']);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'softdelete@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true
        ]);

        $user->delete();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertNull(User::find($user->id));

        // Test global scope active still works (User model has is_active scope logic we touched)
        // Our 'active_user' scope only checks is_active. SoftDeletes handles deleted_at.
        // So a deleted user should be hidden by SoftDeletes.

        $user->restore();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }
}
