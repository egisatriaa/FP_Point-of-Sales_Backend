<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * Report Controller
 */

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * @OA\Get(
     *     path="/admin/reports/summary",
     *     summary="Get sales summary",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"day", "week", "month", "year"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sales summary retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_transactions", type="integer"),
     *                 @OA\Property(property="total_sales", type="number"),
     *                 @OA\Property(property="average_transaction", type="number")
     *             )
     *         )
     *     )
     * )
     */
    public function summary(Request $request)
    {
        return $this->success(
            $this->reportService->getSummary(
                $request->query('period', 'month'),
                $request->query('start_date'),
                $request->query('end_date')
            )
        );
    }

    /**
     * @OA\Get(
     *     path="/admin/reports/top-products",
     *     summary="Get top selling products",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"day", "week", "month", "year"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Top selling products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="product_name", type="string"),
     *                     @OA\Property(property="total_sold", type="integer"),
     *                     @OA\Property(property="total_revenue", type="number")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function topProducts(Request $request)
    {
        return $this->success(
            $this->reportService->getTopProducts(
                $request->query('period', 'month'),
                $request->query('start_date'),
                $request->query('end_date')
            )
        );
    }

    /**
     * @OA\Get(
     *     path="/admin/reports/sales-by-date",
     *     summary="Get sales by date",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"day", "week", "month", "year"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sales by date retrieved successfully",
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="date", type="string"),
     *                     @OA\Property(property="total_transactions", type="integer"),
     *                     @OA\Property(property="total_sales", type="number")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function salesByDate(Request $request)
    {
        return $this->success(
            $this->reportService->getSalesByDate(
                $request->query('period', 'month'),
                $request->query('start_date'),
                $request->query('end_date')
            )
        );
    }

    /**
     * @OA\Get(
     *     path="/admin/reports/top-cashiers",
     *     summary="Get top performing cashiers",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"day", "week", "month", "year"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Top cashiers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="user_id", type="integer"),
     *                     @OA\Property(property="cashier_name", type="string"),
     *                     @OA\Property(property="total_transactions", type="integer"),
     *                     @OA\Property(property="total_sales", type="number")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function topCashiers(Request $request)
    {
        return $this->success(
            $this->reportService->getTopCashiers(
                $request->query('period', 'month'),
                $request->query('start_date'),
                $request->query('end_date'),
                $request->query('limit', 5)
            )
        );
    }
}
