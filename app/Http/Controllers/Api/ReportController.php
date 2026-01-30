<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReportService $reportService
    ) {}

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
}
