<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_report_with_custom_date_range()
    {
        // 1️⃣ Create role (master data)
        $adminRole = Role::create([
            'role_name' => 'admin',
        ]);

        // 2️⃣ Create admin user (NO factory)
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'is_deleted' => false,
        ]);

        // 3️⃣ Create token
        $token = $admin->createToken('test-token')->plainTextToken;

        // 4️⃣ Create category & product
        $category = Category::create([
            'category_name' => 'Food',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-TEST-001',
            'product_name' => 'Burger',
            'description' => 'Test product',
            'price' => 50000,
            'stock' => 100,
        ]);

        // 5️⃣ Create transaction with VALID transaction_date
        $transactionDate = Carbon::parse('2026-01-10 10:00:00');

        $transaction = Transaction::create([
            'transaction_code' => 'TRX-TEST-001',
            'transaction_date' => $transactionDate,
            'total_amount' => 100000,
            'payment_amount' => 100000,
            'change_amount' => 0,
            'status' => 'completed',
            'user_id' => $admin->id,
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price_at_transaction' => 50000,
            'subtotal' => 100000,
        ]);

        // 6️⃣ Call reporting API
        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/reports/summary?start_date=2026-01-01&end_date=2026-01-15');

        // 7️⃣ Assertions
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_sales' => 100000,
                    'total_transactions' => 1,
                ],
            ]);
    }

    public function test_top_products_report_with_custom_date_range()
    {
        // 1️⃣ Create role & admin
        $adminRole = \App\Models\Role::create([
            'role_name' => 'admin',
        ]);

        $admin = \App\Models\User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $token = $admin->createToken('test-token')->plainTextToken;

        // 2️⃣ Create category & products
        $category = \App\Models\Category::create([
            'category_name' => 'Food',
        ]);

        $burger = \App\Models\Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-BURGER',
            'product_name' => 'Burger',
            'description' => 'Burger test',
            'price' => 50000,
            'stock' => 100,
        ]);

        $fries = \App\Models\Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-FRIES',
            'product_name' => 'Fries',
            'description' => 'Fries test',
            'price' => 25000,
            'stock' => 100,
        ]);

        // 3️⃣ Create completed transaction (VALID)
        $transactionDate = \Carbon\Carbon::parse('2026-01-10 12:00:00');

        $transaction = \App\Models\Transaction::create([
            'transaction_code' => 'TRX-TOP-001',
            'transaction_date' => $transactionDate,
            'total_amount' => 175000,
            'payment_amount' => 175000,
            'change_amount' => 0,
            'status' => 'completed',
            'user_id' => $admin->id,
        ]);

        // Burger qty 3
        \App\Models\TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $burger->id,
            'quantity' => 3,
            'price_at_transaction' => 50000,
            'subtotal' => 150000,
        ]);

        // Fries qty 1
        \App\Models\TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $fries->id,
            'quantity' => 1,
            'price_at_transaction' => 25000,
            'subtotal' => 25000,
        ]);

        // 4️⃣ Call Top Products API
        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/reports/top-products?start_date=2026-01-01&end_date=2026-01-15');

        // 5️⃣ Assertions
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.0.product_name', 'Burger')
            ->assertJsonPath('data.0.total_quantity', 3)
            ->assertJsonPath('data.0.total_revenue', 150000)
            ->assertJsonPath('data.1.product_name', 'Fries')
            ->assertJsonPath('data.1.total_quantity', 1)
            ->assertJsonPath('data.1.total_revenue', 25000);
    }

    public function test_sales_by_date_report()
    {
        // Role & admin
        $adminRole = \App\Models\Role::create(['role_name' => 'admin']);

        $admin = \App\Models\User::create([
            'name' => 'Admin',
            'email' => 'admin@chart.test',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $token = $admin->createToken('test-token')->plainTextToken;

        // Transactions on different dates
        \App\Models\Transaction::create([
            'transaction_code' => 'TRX-001',
            'transaction_date' => '2026-01-01 10:00:00',
            'total_amount' => 100000,
            'payment_amount' => 100000,
            'change_amount' => 0,
            'status' => 'completed',
            'user_id' => $admin->id,
        ]);

        \App\Models\Transaction::create([
            'transaction_code' => 'TRX-002',
            'transaction_date' => '2026-01-02 12:00:00',
            'total_amount' => 200000,
            'payment_amount' => 200000,
            'change_amount' => 0,
            'status' => 'completed',
            'user_id' => $admin->id,
        ]);

        // Call API
        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/reports/sales-by-date?start_date=2026-01-01&end_date=2026-01-31');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.0.date', '2026-01-01')
            ->assertJsonPath('data.0.total_sales', 100000)
            ->assertJsonPath('data.1.date', '2026-01-02')
            ->assertJsonPath('data.1.total_sales', 200000);
    }
}
