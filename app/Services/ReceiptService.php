<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Auth\Access\AuthorizationException;

class ReceiptService
{
    public function getByTransaction(Transaction $trx): array
    {
        return [
            'transaction_code' => $trx->transaction_code,
            'transaction_date' => $trx->transaction_date->toDateTimeString(),
            'cashier'          => optional($trx->cashier)->name,
            'status'           => $trx->status,
            'items' => $trx->details->map(fn($d) => [
                'product_name' => $d->product->product_name,
                'quantity'     => $d->quantity,
                'price'        => $d->price_at_transaction,
                'subtotal'     => $d->subtotal,
            ]),
            'total_amount'   => $trx->total_amount,
            'payment_amount' => $trx->payment_amount,
            'change_amount'  => $trx->change_amount,
        ];
    }

    public function authorizeCashier(Transaction $trx, int $userId): void
    {
        if ($trx->user_id !== $userId) {
            throw new AuthorizationException('You are not allowed to access this receipt');
        }
    }
}
