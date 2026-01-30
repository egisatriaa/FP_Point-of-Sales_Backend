<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class ReportService
{
    /**
     * Resolve date range from query params
     */
    public function resolveDateRange(?string $period, ?string $startDate, ?string $endDate): array
    {
        $now = Carbon::now();

        if ($startDate && $endDate) {
            return [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ];
        }

        return match ($period) {
            'day'   => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week'  => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year'  => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Summary report (total sales & total transactions)
     */
    public function getSummary(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array
    {
        [$from, $to] = $this->resolveDateRange($period, $startDate, $endDate);

        $query = Transaction::where('status', 'completed')
            ->whereBetween('transaction_date', [$from, $to]);

        return [
            'total_sales'        => (float) $query->sum('total_amount'),
            'total_transactions' => (int) $query->count(),
            'from'               => $from->toDateTimeString(),
            'to'                 => $to->toDateTimeString(),
        ];
    }

    /**
     * Top selling products
     */
    public function getTopProducts(
        string $period = 'month',
        ?string $startDate = null,
        ?string $endDate = null,
        int $limit = 10
    ) {
        [$from, $to] = $this->resolveDateRange($period, $startDate, $endDate);

        return TransactionDetail::select(
            'products.id as product_id',
            'products.product_name',
            DB::raw('SUM(transaction_details.quantity) as total_quantity'),
            DB::raw('SUM(transaction_details.subtotal) as total_revenue')
        )
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_details.product_id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.transaction_date', [$from, $to])
            ->groupBy('products.id', 'products.product_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    public function getSalesByDate(
        string $period = 'month',
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        [$from, $to] = $this->resolveDateRange($period, $startDate, $endDate);

        $driver = DB::getDriverName();

        // Tentukan date expression berdasarkan DB
        if ($driver === 'sqlite') {
            $dateExpression = match ($period) {
                'year'  => "strftime('%Y-%m', transaction_date)",
                default => "strftime('%Y-%m-%d', transaction_date)",
            };
        } else {
            // MySQL / MariaDB
            $dateExpression = match ($period) {
                'year'  => "DATE_FORMAT(transaction_date, '%Y-%m')",
                default => "DATE_FORMAT(transaction_date, '%Y-%m-%d')",
            };
        }

        return DB::table('transactions')
            ->selectRaw("
            {$dateExpression} as date,
            SUM(total_amount) as total_sales
        ")
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('transaction_date', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
