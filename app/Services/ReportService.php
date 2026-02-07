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
    public function getSummary(string $period = 'month', ?string $startDate = null, ?string $endDate = null, ?int $userId = null): array
    {
        [$from, $to] = $this->resolveDateRange($period, $startDate, $endDate);

        $query = Transaction::where('status', 'completed')
            ->whereBetween('transaction_date', [$from, $to]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return [
            'total_sales'        => (float) $query->sum('total_amount'),
            'total_transactions' => (int) $query->count(),
            'products_sold'      => (int) $query->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
                                            ->sum('transaction_details.quantity'),
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
        ?string $endDate = null,
        ?int $userId = null
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

        $query = DB::table('transactions')
            ->selectRaw("
            {$dateExpression} as date,
            SUM(total_amount) as total_sales
        ")
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('transaction_date', [$from, $to]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Top performers (Cashiers)
     */
    public function getTopCashiers(
        string $period = 'month',
        ?string $startDate = null,
        ?string $endDate = null,
        int $limit = 5
    ) {
        [$from, $to] = $this->resolveDateRange($period, $startDate, $endDate);

        return DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->select(
                'users.id as user_id',
                'users.name as cashier_name',
                DB::raw('COUNT(transactions.id) as total_transactions'),
                DB::raw('SUM(transactions.total_amount) as total_sales')
            )
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.transaction_date', [$from, $to])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_transactions')
            ->limit($limit)
            ->get();
    }

    public function getSalesPerformance(
        string $period = 'month',
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        [$from, $to] = $this->resolveDateRange($period, $startDate, $endDate);
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $dateExpression = match ($period) {
                'year'  => "strftime('%Y-%m', transaction_date)",
                default => "strftime('%Y-%m-%d', transaction_date)",
            };
        } else {
            $dateExpression = match ($period) {
                'year'  => "DATE_FORMAT(transaction_date, '%Y-%m')",
                default => "DATE_FORMAT(transaction_date, '%Y-%m-%d')",
            };
        }

        // Get raw data
        $rawRecords = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->selectRaw("
                {$dateExpression} as date,
                users.name as cashier_name,
                SUM(total_amount) as total_sales
            ")
            ->where('transactions.status', Transaction::STATUS_COMPLETED)
            ->whereBetween('transaction_date', [$from, $to])
            ->groupBy('date', 'cashier_name')
            ->orderBy('date')
            ->get();

        // Transform data for Recharts (Array of {date, [cashier1]: val, [cashier2]: val})
        $formatted = [];
        $dates = $rawRecords->pluck('date')->unique()->values();

        foreach ($dates as $date) {
            $entry = ['date' => $date];
            foreach ($rawRecords->where('date', $date) as $record) {
                $entry[$record->cashier_name] = (float) $record->total_sales;
            }
            $formatted[] = $entry;
        }

        return $formatted;
    }
}
