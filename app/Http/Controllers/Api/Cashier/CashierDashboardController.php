<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class CashierDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * @OA\Get(
     *     path="/cashier/dashboard/stats",
     *     summary="Get dashboard statistics (Personal)",
     *     tags={"Cashier Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         @OA\Schema(type="string", enum={"day", "week", "month", "year"})
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             example={
     *                 "success": true,
     *                 "message": "Success",
     *                 "data": {
     *                     "total_sales": 500000,
     *                     "total_transactions": 5,
     *                     "from": "2026-02-01 00:00:00",
     *                     "to": "2026-02-28 23:59:59"
     *                 }
     *             }
     *         )
     *     )
     * )
     */
    public function stats(Request $request)
    {
        $userId = $request->user()->id;

        $data = $this->reportService->getSummary(
            $request->query('period', 'month'),
            $request->query('start_date'),
            $request->query('end_date'),
            $userId // Filter by current user
        );

        return $this->success($data);
    }

    /**
     * @OA\Get(
     *     path="/cashier/dashboard/chart",
     *     summary="Get dashboard chart data (Personal)",
     *     tags={"Cashier Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         @OA\Schema(type="string", enum={"day", "week", "month", "year"})
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     )
     * )
     */
    public function chart(Request $request)
    {
        $userId = $request->user()->id;

        $data = $this->reportService->getSalesByDate(
            $request->query('period', 'month'),
            $request->query('start_date'),
            $request->query('end_date'),
            $userId // Filter by current user
        );

        return $this->success($data);
    }

    /**
     * @OA\Get(
     *     path="/cashier/dashboard/recent-sales",
     *     summary="Get recent sales (Personal)",
     *     tags={"Cashier Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         @OA\Schema(type="integer", default=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     )
     * )
     */
    public function recentSales(Request $request)
    {
        $userId = $request->user()->id;
        $limit = (int) $request->query('limit', config('pos.dashboard_recent_limit', 10));

        $recent = Transaction::with('cashier')
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('user_id', $userId) // Filter by current user
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($trx) => [
                'transaction_code' => $trx->transaction_code,
                'total_amount'     => $trx->total_amount,
                'cashier_name'     => $trx->cashier->name ?? 'Unknown',
                'created_at'       => $trx->created_at 
                    ? $trx->created_at->toIso8601String() 
                    : $trx->transaction_date->toIso8601String(),
            ]);

        return $this->success($recent);
    }
}
