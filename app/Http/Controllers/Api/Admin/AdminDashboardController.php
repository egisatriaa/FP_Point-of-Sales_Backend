<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * @OA\Get(
     *     path="/admin/dashboard/stats",
     *     summary="Get dashboard statistics (Global)",
     *     tags={"Admin Dashboard"},
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
     *                     "total_sales": 1500000,
     *                     "total_transactions": 25,
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
        $data = $this->reportService->getSummary(
            $request->query('period', 'month'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return $this->success([
            'total_revenue'      => (float) $data['total_sales'],
            'total_transactions' => (int) $data['total_transactions'],
            'products_sold'      => (int) $data['products_sold'],
            'average_transaction' => $data['total_transactions'] > 0 
                ? (float) ($data['total_sales'] / $data['total_transactions']) 
                : 0
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/dashboard/chart",
     *     summary="Get dashboard chart data (Global)",
     *     tags={"Admin Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="range",
     *         in="query",
     *         description="e.g., 7d, 30d",
     *         @OA\Schema(type="string")
     *     ),
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
        $period = $request->query('period', 'month');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($request->filled('range')) {
            $range = $request->query('range');
            if (preg_match('/^(\d+)d$/', $range, $matches)) {
                $days = (int) $matches[1];
                $startDate = now()->subDays($days - 1)->toDateString();
                $endDate = now()->toDateString();
                $period = 'day';
            }
        }

        $data = $this->reportService->getSalesByDate(
            $period,
            $startDate,
            $endDate
        );

        $mappedData = $data->map(function ($item) {
            return [
                'date' => $item->date,
                'total' => (float) $item->total_sales,
            ];
        });

        return $this->success($mappedData);
    }

    /**
     * @OA\Get(
     *     path="/admin/dashboard/recent-sales",
     *     summary="Get recent sales (Global)",
     *     tags={"Admin Dashboard"},
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
        $limit = (int) $request->query('limit', config('pos.dashboard_recent_limit', 10));

        // Fix: Use transaction_date instead of created_at because timestamps are false in model
        $recent = Transaction::with('cashier')
            ->where('status', Transaction::STATUS_COMPLETED)
            ->orderByDesc('transaction_date') 
            ->limit($limit)
            ->get()
            ->map(fn($trx) => [
                'id'               => $trx->transaction_code, // Map transaction_code to id for Frontend
                'transaction_code' => $trx->transaction_code,
                'total_price'      => (float) $trx->total_amount, // Map total_amount to total_price
                'cashier_name'     => $trx->cashier->name ?? 'Unknown',
                'created_at'       => $trx->transaction_date instanceof \Carbon\Carbon 
                    ? $trx->transaction_date->toIso8601String() 
                    : \Carbon\Carbon::parse($trx->transaction_date)->toIso8601String(),
            ]);

        return $this->success($recent);
    }

    /**
     * @OA\Get(
     *     path="/admin/dashboard/performance",
     *     summary="Get dashboard performance data (Area Chart)",
     *     tags={"Admin Dashboard"},
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
    public function performance(Request $request)
    {
        $data = $this->reportService->getSalesPerformance(
            $request->query('period', 'month'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return $this->success($data);
    }
}
