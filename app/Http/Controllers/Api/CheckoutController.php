<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\CheckoutRequest;
use App\Services\TransactionService;
use App\Traits\ApiResponse;

class CheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function checkout(CheckoutRequest $request)
    {
        try {
            $transaction = $this->transactionService->checkout(
                $request->user()->id,
                $request->payment_amount
            );

            return $this->success([
                'transaction_id'   => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'total_amount'     => $transaction->total_amount,
                'payment_amount'   => $transaction->payment_amount,
                'change_amount'    => $transaction->change_amount,
            ], 'Transaction completed', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
