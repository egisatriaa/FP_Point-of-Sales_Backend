<?php

namespace App\Http\Controllers\Api\Cashier;

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
     *     name="search",
     *     in="query",
     *     description="Filter by transaction_code (LIKE)",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="date_from",
     *     in="query",
     *     description="Filter start date (YYYY-MM-DD)",
     *     required=false,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="date_to",
     *     in="query",
     *     description="Filter end date (YYYY-MM-DD)",
     *     required=false,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="period",
     *     in="query",
     *     description="day | week | month | year (Legacy)",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="start_date",
     *     in="query",
     *     description="Custom start date (Legacy)",
     *     required=false,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="end_date",
     *     in="query",
     *     description="Custom end date (Legacy)",
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

        // 1. Search Filter (Scope: transaction_code only)
        // NOTE: customer_name is not in the schema, so we restrict search to transaction_code.
        $query->when($request->query('search'), function ($q, $search) {
             $q->where('transaction_code', 'like', "%{$search}%");
        });

        // 2. Date Range Filter
        // Use 'transaction_date' column.
        if ($request->has(['date_from', 'date_to'])) {
            $query->whereBetween('transaction_date', [
                $request->query('date_from'), 
                $request->query('date_to') . ' 23:59:59'
            ]);
        }
        // Legacy support 
        elseif ($request->hasAny(['period', 'start_date', 'end_date'])) {
            [$from, $to] = $this->reportService->resolveDateRange(
                $request->query('period'),
                $request->query('start_date'),
                $request->query('end_date')
            );
            $query->whereBetween('transaction_date', [$from, $to]);
        }

        // 3. Payment Method Filter REMOVED (Column does not exist)

        $perPage = (int) $request->query('per_page', 10);
        $paginator = $query->paginate($perPage);

        // Custom Response Structure with Meta
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data'    => $paginator->getCollection()->transform(fn($trx) => [
                'transaction_id'   => $trx->id,
                'transaction_code' => $trx->transaction_code,
                'transaction_date' => $trx->transaction_date->toDateTimeString(),
                'total_amount'     => (float) $trx->total_amount,
                'payment_amount'   => (float) $trx->payment_amount,
                'change_amount'    => (float) $trx->change_amount,
                'payment_method'   => $trx->payment_method, // May be null if column missing
                'status'           => $trx->status,
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *   path="/transactions/my/{id_or_code}",
     *   tags={"Transactions (Cashier)"},
     *   summary="Get cashier transaction detail",
     *   description="Show detail by ID or Transaction Code",
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
     *     name="id_or_code",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", description="Transaction ID (int) or Code (string)")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful response"
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden"
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Transaction not found"
     *   )
     * )
     */
    public function show($idOrCode, Request $request)
    {
        $query = Transaction::with(['details.product', 'cashier']) // Added 'cashier' relation
            ->where('user_id', $request->user()->id);

        if (is_numeric($idOrCode)) {
            $query->where('id', $idOrCode);
        } else {
            $query->where('transaction_code', $idOrCode);
        }

        $trx = $query->first();

        if (! $trx) {
            return $this->error('Transaction not found or access denied', 404);
        }

        return $this->success([
            'transaction_id'   => $trx->id,
            'transaction_code' => $trx->transaction_code,
            'transaction_date' => $trx->transaction_date->toDateTimeString(),
            'status'           => $trx->status,
            'total_amount'     => (float) $trx->total_amount,
            'payment_amount'   => (float) $trx->payment_amount,
            'change_amount'    => (float) $trx->change_amount,
            'payment_method'   => $trx->payment_method,
            'payment_channel'  => $trx->payment_channel, // Added payment_channel
            'cashier_name'     => $trx->cashier->name ?? 'Unknown', // Added cashier_name
            'items' => $trx->details->map(fn($d) => [
                'product_id'   => $d->product_id,
                'product_name' => $d->product->product_name,
                'quantity'     => (int) $d->quantity,
                'price'        => (float) $d->price_at_transaction,
                'subtotal'     => (float) $d->subtotal,
            ]),
        ]);
    }
    /**
     * @OA\Post(
     *     path="/transactions",
     *     summary="Create new transaction (Stateless)",
     *     tags={"Transactions (Cashier)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"items", "paid_amount"},
     *                 @OA\Property(property="payment_method", type="string", example="cash"),
     *                 @OA\Property(property="paid_amount", type="number", example=50000),
     *                 @OA\Property(property="items", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="product_id", type="integer", example=1),
     *                         @OA\Property(property="qty", type="integer", example=2)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction created",
     *     )
     * )
     */
    public function store(\App\Http\Requests\Transaction\StoreTransactionRequest $request)
    {
        try {
            // Note: StoreTransactionRequest merges 'paid_amount' into 'payment_amount'
            $transaction = \App\Services\TransactionService::class; // Typo fix in thought checks, simpler to use dependency injection
            // The controller already imports ReportService, I need TransactionService injected or use the one from constructor if I add it.
            // Wait, existing constructor only has ReportService. I need to inject TransactionService.
            // I will use `app(...)` helper or add to constructor in a separate edit if strictly needed, 
            // but for now let's modify the constructor in this same replacement if possible or use method injection or app().
            
            // Let's us app() for minimal changes or check if I can update constructor cleanly.
            // The file content shows constructor only has ReportService.
            // I will use method injection for the service in the method signature or app(TransactionService::class)
            
            $service = app(\App\Services\TransactionService::class);
            
            $transaction = $service->createTransaction(
                $request->user()->id,
                $request->items,
                $request->payment_amount
            );

            return $this->success([
                'transaction_id'   => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'total_amount'     => $transaction->total_amount,
                'payment_amount'   => $transaction->payment_amount,
                'change_amount'    => $transaction->change_amount,
            ], 'Transaction created successfully', 201);

        } catch (\App\Exceptions\BusinessException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Transaction Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Internal Server Error', 500);
        }
    }
}
