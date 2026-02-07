<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Midtrans\Snap;
use Laravel\Sanctum\Sanctum;

class MidtransIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock Midtrans Snap to prevent actual API calls
        $this->mock('alias:Midtrans\Snap', function ($mock) {
            $mock->shouldReceive('getSnapToken')->andReturn('dummy-snap-token-123');
        });
    }

    private function createScenario()
    {
        // Ensure role exists
        $role = \App\Models\Role::firstOrCreate(['role_name' => 'cashier']);
        
        $user = User::factory()->create(['role_id' => $role->id]);
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 50000
        ]);
        
        $cart = Cart::create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 50000
        ]);

        return compact('user', 'product', 'cart');
    }

    public function test_checkout_midtrans_flow()
    {
        $data = $this->createScenario();
        $user = $data['user'];
        $product = $data['product'];

        // Bypass Role Middleware to focus on Midtrans Logic
        // $this->withoutMiddleware([\App\Http\Middleware\RoleMiddleware::class]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'midtrans',
            'paid_amount' => 100000 // Exact amount
        ]);
        
        if ($response->status() !== 200) {
           dump($response->json());
        }

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'transaction_code',
                    'snap_token',
                    'status',
                    'total_amount'
                ]
            ]);
        
        $this->assertEquals('dummy-snap-token-123', $response->json('data.snap_token'));
        $this->assertEquals('pending', $response->json('data.status'));

        // Check DB State
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'total_amount' => 100000,
            'status' => 'pending',
            'snap_token' => 'dummy-snap-token-123'
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8 // 10 - 2
        ]);

        $this->assertDatabaseHas('carts', [
            'id' => $data['cart']->id,
            'status' => 'completed'
        ]);

        return $response->json('data.transaction_code');
    }

    public function createPendingTransaction()
    {
        $data = $this->createScenario();
        $user = $data['user'];
        $product = $data['product'];
        
        $trxCode = 'TRX-' . rand(1000,9999);
        
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'transaction_code' => $trxCode,
            'transaction_date' => now(),
            'total_amount' => 100000,
            'payment_amount' => 100000,
            'change_amount' => 0,
            'payment_method' => 'midtrans',
            'status' => 'pending',
            'snap_token' => 'dummy-token'
        ]);
        
        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price_at_transaction' => 50000,
            'subtotal' => 100000
        ]);
        
        // Deduct stock manually as checkout would
        $product->decrement('stock', 2);
        
        return $trxCode;
    }

    public function test_webhook_success_simulation()
    {
        $trxCode = $this->createPendingTransaction();
        $serverKey = config('midtrans.server_key');
        
        $payload = [
            'order_id' => $trxCode,
            'status_code' => '200',
            'gross_amount' => '100000',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'credit_card'
        ];

        // Signature = SHA512(order_id + status_code + gross_amount + server_key)
        $signature = hash("sha512", $trxCode . '200' . '100000' . $serverKey);
        $payload['signature_key'] = $signature;

        $response = $this->postJson('/api/webhooks/midtrans', $payload);
        
        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'transaction_code' => $trxCode,
            'status' => 'completed',
            'gateway_status' => 'settlement'
        ]);
    }

    public function test_webhook_expire_simulation_stock_reversal()
    {
        $trxCode = $this->createPendingTransaction();
        
        // Initial Stock is 8 (after checkout deduction)
        // We expect it to go back to 10
        
        $serverKey = config('midtrans.server_key');
        
        $payload = [
            'order_id' => $trxCode,
            'status_code' => '200',
            'gross_amount' => '100000',
            'transaction_status' => 'expire',
        ];

        $signature = hash("sha512", $trxCode . '200' . '100000' . $serverKey);
        $payload['signature_key'] = $signature;

        $response = $this->postJson('/api/webhooks/midtrans', $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'transaction_code' => $trxCode,
            'status' => 'expired'
        ]);

        // Stock Reversal Check
        $this->assertEquals(10, Product::first()->stock);
    }
    
    public function test_idempotency_webhook()
    {
        $trxCode = $this->createPendingTransaction();
        $serverKey = config('midtrans.server_key');
        
        $payload = [
            'order_id' => $trxCode,
            'status_code' => '200',
            'gross_amount' => '100000',
            'transaction_status' => 'settlement',
        ];
        $signature = hash("sha512", $trxCode . '200' . '100000' . $serverKey);
        $payload['signature_key'] = $signature;

        // First Call
        $this->postJson('/api/webhooks/midtrans', $payload)->assertStatus(200);
        
        // Second Call
        $this->postJson('/api/webhooks/midtrans', $payload)->assertStatus(200);
        
        $this->assertDatabaseHas('transactions', [
            'transaction_code' => $trxCode,
            'status' => 'completed'
        ]);
    }

    public function test_signature_reject()
    {
        $trxCode = $this->createPendingTransaction();
        
        $payload = [
            'order_id' => $trxCode,
            'status_code' => '200',
            'gross_amount' => '100000',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid-signature'
        ];

        $response = $this->postJson('/api/webhooks/midtrans', $payload);
        
        $response->assertStatus(403);
    }
}
