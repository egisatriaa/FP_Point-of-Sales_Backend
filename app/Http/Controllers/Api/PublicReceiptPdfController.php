<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ReceiptService;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenApi\Annotations as OA;

class PublicReceiptPdfController extends Controller
{
    public function __construct(
        protected ReceiptService $receiptService
    ) {}

    /**
     * @OA\Get(
     *     path="/receipt/{transaction_code}/pdf",
     *     summary="Download public receipt PDF",
     *     tags={"Public Receipts"},
     *     @OA\Parameter(
     *         name="transaction_code",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF Download",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     )
     * )
     */
    public function download(string $transactionCode)
    {
        $trx = Transaction::with(['details.product', 'cashier'])
            ->where('transaction_code', $transactionCode)
            ->where('status', 'completed')
            ->firstOrFail();

        $receipt = $this->receiptService->getByTransaction($trx);

        $pdf = Pdf::loadView('receipt.pdf', compact('receipt'));

        return $pdf->download(
            'receipt-' . $trx->transaction_code . '.pdf'
        );
    }
}
