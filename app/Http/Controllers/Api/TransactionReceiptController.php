<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ReceiptService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class TransactionReceiptController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReceiptService $receiptService
    ) {}

    /**
     * @OA\Get(
     *     path="/transactions/{id}/receipt",
     *     summary="View transaction receipt",
     *     tags={"Receipts Cashier"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Receipt details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                  @OA\Property(property="transaction_code", type="string"),
     *                  @OA\Property(property="date", type="string"),
     *                  @OA\Property(property="cashier", type="string"),
     *                  @OA\Property(property="total_amount", type="number"),
     *                  @OA\Property(property="payment_amount", type="number"),
     *                  @OA\Property(property="change_amount", type="number")
     *             )
     *         )
     *     )
     * )
     */
    public function show(int $id, Request $request)
    {
        $trx = Transaction::with(['cashier', 'details.product'])
            ->findOrFail($id);

        if ($request->user()->role->role_name === 'cashier') {
            $this->receiptService->authorizeCashier(
                $trx,
                $request->user()->id
            );
        }

        return $this->success(
            $this->receiptService->getByTransaction($trx)
        );
    }
}
