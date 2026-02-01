<?php

namespace App\Http\Controllers\Api\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ReportService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use OpenApi\Annotations as OA;

class KasirTransactionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * @OA\Get(
     *   path="/transactions/my",
     *   summary="Get cashier transaction list",
     *   description="Internal transaction list (not receipt)",
     *   tags={"Transactions (Cashier)"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", example="application/json")
     *   ),
     *
     *   @OA\Parameter(
     *     name="period",
     *     in="query",
     *     description="day | week | month | year",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="start_date",
     *     in="query",
     *     description="Custom start date (YYYY-MM-DD)",
     *     required=false,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="end_date",
     *     in="query",
     *     description="Custom end date (YYYY-MM-DD)",
     *     required=false,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="per_page",
     *     in="query",
     *     description="Items per page",
     *     required=false,
     *     @OA\Schema(type="integer", default=10)
     *   ),
     *   @OA\Response(
     *   response=200,
     *   description="Successful response",
     *   @OA\JsonContent(
     *     example={
     *       "success": true,
     *       "message": "Success",
     *       "data": {
     *         "current_page": 1,
     *         "per_page": 10,
     *         "total": 3,
     *         "data": {
     *           {
     *             "transaction_id": 3,
     *             "transaction_code": "TRX-20260131212326-XGGJ",
     *             "transaction_date": "2026-01-31 21:23:26",
     *             "total_amount": "46000.00",
     *             "payment_amount": "50000.00",
     *             "change_amount": "4000.00",
     *             "status": "completed"
     *           }
     *         }
     *       }
     *     }
     *   )
     * )
     * )
     */
    public function index(Request $request)
    {
        $query = Transaction::where('user_id', $request->user()->id)
            ->orderByDesc('transaction_date');

        // Filter tanggal hanya jika parameter dikirim
        if ($request->hasAny(['period', 'start_date', 'end_date'])) {
            [$from, $to] = $this->reportService->resolveDateRange(
                $request->query('period'),
                $request->query('start_date'),
                $request->query('end_date')
            );
            $query->whereBetween('transaction_date', [$from, $to]);
        }

        $perPage = (int) $request->query('per_page', 10);

        $transactions = $query->paginate($perPage)
            ->through(fn($trx) => [
                'transaction_id'   => $trx->id,
                'transaction_code' => $trx->transaction_code,
                'transaction_date' => $trx->transaction_date->toDateTimeString(),
                'total_amount'     => $trx->total_amount,
                'payment_amount'   => $trx->payment_amount,
                'change_amount'    => $trx->change_amount,
                'status'           => $trx->status,
            ]);

        return $this->success($transactions);
    }

    /**
     * @OA\Get(
     *   path="/transactions/my/{id}",
     *   tags={"Transactions (Cashier)"},
     *   summary="Get cashier transaction detail",
     *   description="Show detail of a transaction created by the authenticated cashier",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", example="application/json")
     *   ),
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *   response=200,
     *   description="Successful response",
     *   @OA\JsonContent(
     *     example={
     *       "success": true,
     *       "message": "Success",
     *       "data": {
     *         "transaction_code": "TRX-20260131212326-XGGJ",
     *         "transaction_date": "2026-01-31 21:23:26",
     *         "status": "completed",
     *         "total_amount": "46000.00",
     *         "payment_amount": "50000.00",
     *         "change_amount": "4000.00",
     *         "items": {
     *           {
     *             "product_id": 5,
     *             "product_name": "Nasi Kuning Spesial",
     *             "quantity": 2,
     *             "price": "18000.00",
     *             "subtotal": "36000.00"
     *           }
     *         }
     *       }
     *     }
     *   )
     * ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden"
     *   ),
     *   @OA\Response(
     *   response=404,
     *   description="Transaction not found",
     *   @OA\JsonContent(
     *     example={
     *       "success": false,
     *       "message": "Transaction not found or access denied"
     *     }
     *   )
     * )
     * )
     */
    public function show(int $id, Request $request)
    {
        $trx = Transaction::with('details.product')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $trx) {
            return $this->error('Transaction not found or access denied', 404);
        }

        return $this->success([
            'transaction_code' => $trx->transaction_code,
            'transaction_date' => $trx->transaction_date->toDateTimeString(),
            'status'           => $trx->status,
            'total_amount'     => $trx->total_amount,
            'payment_amount'   => $trx->payment_amount,
            'change_amount'    => $trx->change_amount,
            'items' => $trx->details->map(fn($d) => [
                'product_id'   => $d->product_id,
                'product_name' => $d->product->product_name,
                'quantity'     => $d->quantity,
                'price'        => $d->price_at_transaction,
                'subtotal'     => $d->subtotal,
            ]),
        ]);
    }
}
