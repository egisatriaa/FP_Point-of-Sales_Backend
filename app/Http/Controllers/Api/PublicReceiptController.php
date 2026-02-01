<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ReceiptService;
use App\Traits\ApiResponse;
use OpenApi\Annotations as OA;

class PublicReceiptController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReceiptService $receiptService
    ) {}

    /**
     * @OA\Get(
     *   path="/receipt/{transaction_code}",
     *   summary="View public receipt",
     *   description="Get public receipt details by transaction code (only completed transactions)",
     *   tags={"Public Receipts"},
     *
     *   @OA\Parameter(
     *     name="transaction_code",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", example="TRX-20260131212326-XGGJ")
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Receipt retrieved successfully",
     *     @OA\JsonContent(
     *       example={
     *         "success": true,
     *         "message": "Success",
     *         "data": {
     *           "transaction_code": "TRX-20260131212326-XGGJ",
     *           "transaction_date": "2026-01-31 21:23:26",
     *           "cashier": "Cashier User",
     *           "status": "completed",
     *           "items": {
     *             {
     *               "product_name": "Nasi Kuning Spesial",
     *               "quantity": 2,
     *               "price": "18000.00",
     *               "subtotal": "36000.00"
     *             }
     *           },
     *           "total_amount": "46000.00",
     *           "payment_amount": "50000.00",
     *           "change_amount": "4000.00"
     *         }
     *       }
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response=404,
     *     description="Receipt not found",
     *     @OA\JsonContent(
     *       example={
     *         "success": false,
     *         "message": "Transaction not found or not completed"
     *       }
     *     )
     *   )
     * )
     */
    public function show(string $transactionCode)
    {
        $trx = Transaction::with(['details.product', 'cashier'])
            ->where('transaction_code', $transactionCode)
            ->where('status', 'completed')
            ->firstOrFail();

        return $this->success(
            $this->receiptService->getByTransaction($trx)
        );
    }

    /**
     * @OA\Get(
     *   path="/receipt/{transaction_code}/pdf-link",
     *   summary="Get public receipt PDF link",
     *   description="Return downloadable PDF URL for public receipt",
     *   tags={"Public Receipts"},
     *
     *   @OA\Parameter(
     *     name="transaction_code",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", example="TRX-20260131212326-XGGJ")
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="PDF link generated",
     *     @OA\JsonContent(
     *       example={
     *         "success": true,
     *         "message": "Success",
     *         "data": {
     *           "pdf_url": "http://localhost:8000/api/receipt/TRX-20260131212326-XGGJ/pdf"
     *         }
     *       }
     *     )
     *   )
     * )
     */
    public function pdfLink(string $transactionCode)
    {
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'pdf_url' => url("/api/receipt/{$transactionCode}/pdf"),
            ],
        ]);
    }
}
