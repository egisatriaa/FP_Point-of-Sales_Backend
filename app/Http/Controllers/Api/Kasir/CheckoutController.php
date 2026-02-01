<?php

namespace App\Http\Controllers\Api\Kasir;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Http\Requests\Transaction\CheckoutRequest;
use App\Services\TransactionService;
use App\Traits\ApiResponse;

/**
 * Checkout Controller
 */

class CheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * @OA\Post(
     *     path="/checkout",
     *     summary="Checkout cart items",
     *     tags={"Checkout"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Checkout payment details",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"payment_amount"},
     *                 @OA\Property(property="payment_amount", type="number", example=100000)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction completed"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction_id", type="integer", example=1),
     *                 @OA\Property(property="transaction_code", type="string", example="TRX-123456"),
     *                 @OA\Property(property="total_amount", type="number", example=90000),
     *                 @OA\Property(property="payment_amount", type="number", example=100000),
     *                 @OA\Property(property="change_amount", type="number", example=10000)
     *             )
     *         )
     *     )
     * )
     */
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
