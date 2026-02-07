<?php

namespace App\Services;

use App\Models\Transaction;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createSnapToken(Transaction $trx)
    {
        // Gross amount must be integer
        $grossAmount = (int) round($trx->total_amount);

        $params = [
            'transaction_details' => [
                'order_id' => $trx->transaction_code,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $trx->user->name ?? 'Guest',
                'email' => $trx->user->email ?? 'guest@pos.com',
            ],
            'item_details' => $trx->details->map(function ($item) {
                return [
                    'id' => substr((string) ($item->product->sku ?? $item->product_id), 0, 50),
                    'price' => (int) round($item->price_at_transaction),
                    'quantity' => (int) $item->quantity,
                    'name' => substr($item->product->product_name ?? 'Item', 0, 50),
                ];
            })->toArray(),
        ];

        return Snap::getSnapToken($params);
    }
}
