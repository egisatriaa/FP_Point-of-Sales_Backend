<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenApi\Annotations as OA;

class ReceiptPdfController extends Controller
{
    public function __construct(
        protected ReceiptService $receiptService
    ) {}

    /**
     * @OA\Get(
     *     path="/transactions/{id}/receipt/pdf",
     *     summary="Download receipt PDF",
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
     *         description="PDF Download",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     )
     * )
     */
    public function download(int $id, Request $request)
    {
        $trx = Transaction::with(['cashier', 'details.product'])
            ->findOrFail($id);

        // Kasir hanya boleh lihat transaksinya sendiri
        if ($request->user()->role->role_name === 'cashier') {
            $this->receiptService->authorizeCashier($trx, $request->user()->id);
        }

        $receipt = $this->receiptService->getByTransaction($trx);

        $pdf = Pdf::loadView('receipt.pdf', compact('receipt'));

        return $pdf->download(
            'receipt-' . $trx->transaction_code . '.pdf'
        );
    }
}
