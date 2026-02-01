<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ReportService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use OpenApi\Annotations as OA;

class TransactionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * @OA\Get(
     *   path="/admin/transactions",
     *   tags={"Transactions"},
     *   summary="List transactions (read-only)",
     *   description="Get paginated list of transactions with optional date filtering",
     *   security={{"bearerAuth":{}}},
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
     *     response=200,
     *     description="Successful response"
     *   )
     * )
     */
    public function index(Request $request)
    {
        $query = Transaction::with('cashier')
            ->orderByDesc('transaction_date');

        // APPLY FILTER HANYA JIKA ADA PARAMETER
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
                'cashier'          => optional($trx->cashier)->name,
            ]);

        return $this->success($transactions);
    }


    /**
     * @OA\Get(
     *   path="/admin/transactions/{id}",
     *   tags={"Transactions"},
     *   summary="Get transaction detail",
     *   description="Show transaction with its item details (read-only)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful response"
     *   )
     * )
     */
    public function show(int $id)
    {
        $trx = Transaction::with(['cashier', 'details.product'])
            ->findOrFail($id);

        return $this->success([
            'transaction_code' => $trx->transaction_code,
            'transaction_date' => $trx->transaction_date->toDateTimeString(),
            'status'           => $trx->status,
            'total_amount'     => $trx->total_amount,
            'payment_amount'   => $trx->payment_amount,
            'change_amount'    => $trx->change_amount,
            'cashier' => [
                'id'   => optional($trx->cashier)->id,
                'name' => optional($trx->cashier)->name,
            ],
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
