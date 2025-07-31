<?php

namespace App\Contracts\Repositories;

use App\Contracts\Interfaces\ProfitLossInterface;
use App\Enums\StockRequestStatus;
use App\Enums\TransactionStatus;
use App\Models\Pengeluaran;
use App\Models\StockRequest;
use App\Models\Transaction;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

final class ProfitLossRepository extends BaseRepository implements ProfitLossInterface
{
    public function getOutletProfitLoss(string $outlet_id, ?int $month = null, ?int $year = null): array
    {
        $incomeQuery = Transaction::where('outlet_id', $outlet_id)
            ->where('transaction_status', TransactionStatus::COMPLETE);

        $requestSpendingQuery = StockRequest::query()
            ->where('outlet_id', $outlet_id)
            ->where('status', StockRequestStatus::APPROVED);

        $categorySpendingsQuery = Pengeluaran::query()
            ->where('outlet_id', $outlet_id);

        if ($month) {
            $incomeQuery->whereMonth('payment_time', $month);
            $requestSpendingQuery->whereMonth('created_at', $month);
            $categorySpendingsQuery->whereMonth('tanggal_pengeluaran', $month);
        }

        if ($year) {
            $incomeQuery->whereYear('payment_time', $year);
            $requestSpendingQuery->whereYear('created_at', $year);
            $categorySpendingsQuery->whereYear('tanggal_pengeluaran', $year);
        }

        $income = $incomeQuery->sum('amount_price');
        $requestSpending = $requestSpendingQuery->sum('total_price');

        $categorySpendings = $categorySpendingsQuery
            ->groupBy('kategori_pengeluaran_id')
            ->selectRaw('kategori_pengeluaran_id, SUM(nominal_pengeluaran) as total_pengeluaran')
            ->get();

        $totalCategorySpendings = $categorySpendings->sum('total_pengeluaran');

        $totalSpending = $requestSpending + $totalCategorySpendings;

        return [
            'pendapatan' => (float) $income,
            'pengeluaran' => [
                'request_stock' => (float) $requestSpending,
                'pengeluaran_lain' => (float) $totalCategorySpendings,
                'total' => (float) $totalSpending,
            ],
            'laba_rugi' => (float) $income - (float) $totalSpending,
        ];
    }

    public function getWarehouseProfitLoss(string $warehouse_id, ?int $month = null, ?int $year = null): array
    {
        $transactionIncomeQuery = Transaction::where('warehouse_id', $warehouse_id)
            ->where('transaction_status', TransactionStatus::COMPLETE);

        $requestIncomeQuery = StockRequest::where('warehouse_id', $warehouse_id)
            ->where('status', StockRequestStatus::APPROVED);

        $warehouseSpendingsQuery = WarehouseStock::query();

        $categorySpendingsQuery = Pengeluaran::where('warehouse_id', $warehouse_id);

        if ($month) {
            $transactionIncomeQuery->whereMonth('payment_time', $month);
            $requestIncomeQuery->whereMonth('created_at', $month);
            $warehouseSpendingsQuery->whereMonth('created_at', $month);
            $categorySpendingsQuery->whereMonth('tanggal_pengeluaran', $month);
        }

        if ($year) {
            $transactionIncomeQuery->whereYear('payment_time', $year);
            $requestIncomeQuery->whereYear('created_at', $year);
            $warehouseSpendingsQuery->whereYear('created_at', $year);
            $categorySpendingsQuery->whereYear('tanggal_pengeluaran', $year);
        }

        $transactionIncome = $transactionIncomeQuery->sum('amount_price');
        $requestIncome = $requestIncomeQuery->sum('total_price');
        $totalIncome = $transactionIncome + $requestIncome;

        $warehouseSpendings = $warehouseSpendingsQuery
            ->groupBy('product_detail_id')
            ->selectRaw('product_detail_id, SUM(total_price) as total_pengeluaran')
            ->get();
        $totalWarehouseSpendings = $warehouseSpendings->sum('total_pengeluaran');

        $categorySpendings = $categorySpendingsQuery
            ->groupBy('kategori_pengeluaran_id')
            ->selectRaw('kategori_pengeluaran_id, SUM(nominal_pengeluaran) as total_pengeluaran')
            ->get();
        $totalCategorySpendings = $categorySpendings->sum('total_pengeluaran');

        $totalSpending = $totalWarehouseSpendings + $totalCategorySpendings;

        return [
            'pendapatan' => [
                'transaksi' => (float) $transactionIncome,
                'stock_request' => (float) $requestIncome,
                'total' => (float) $totalIncome,
            ],
            'pengeluaran' => [
                'warehouse_stock' => (float) $totalWarehouseSpendings,
                'pengeluaran_lain' => (float) $totalCategorySpendings,
                'total' => (float) $totalSpending,
            ],
            'laba_rugi' => (float) $totalIncome - (float) $totalSpending,
        ];
    }
}
