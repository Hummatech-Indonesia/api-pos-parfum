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
    public function getOutletProfitLoss(string $outlet_id): array
    {
        $income = Transaction::where('outlet_id', $outlet_id)
            ->where('transaction_status', TransactionStatus::COMPLETE)
            ->sum('amount_price');

        $requestSpending = StockRequest::query()
            ->where('outlet_id', $outlet_id)
            ->where('status', StockRequestStatus::APPROVED)
            ->sum('total_price');

        $categorySpendings = Pengeluaran::query()
            ->where('outlet_id', $outlet_id)
            ->groupBy('kategori_pengeluaran_id')
            ->selectRaw('kategori_pengeluaran_id, SUM(nominal_pengeluaran) as total_pengeluaran')
            ->get();

        $totalCategorySpendings = $categorySpendings->sum('total_pengeluaran');

        $totalSpending = $requestSpending + $totalCategorySpendings;

        return [
            'pendapatan' => (float) $income,
            'pengeluaran' => [
                'request_stock' => (float) $requestSpending,
                'pengeluaran_lain' => $categorySpendings,
                'total' => (float) $totalSpending,
            ],
            'laba_rugi' => (float) $income - (float) $totalSpending,
        ];
    }

    public function getWarehouseProfitLoss(string $warehouse_id): array
    {
        $transactionIncome = Transaction::where('warehouse_id', $warehouse_id)
            ->where('transaction_status', TransactionStatus::COMPLETE)
            ->sum('amount_price');

        $requestIncome = StockRequest::where('warehouse_id', $warehouse_id)
            ->where('status', StockRequestStatus::APPROVED)
            ->sum('total_price');

        $totalIncome = $transactionIncome + $requestIncome;

        $warehouseSpendings = WarehouseStock::query()
            ->groupBy('product_detail_id')
            ->selectRaw('product_detail_id, SUM(total_price) as total_pengeluaran')
            ->get();

        $totalWarehouseSpendings = $warehouseSpendings->sum('total_pengeluaran');

        $categorySpendings = Pengeluaran::query()
            ->where('warehouse_id', $warehouse_id)
            ->groupBy('kategori_pengeluaran_id')
            ->selectRaw('kategori_pengeluaran_id, SUM(nominal_pengeluaran) as total_pengeluaran')
            ->get();

        $totalCategorySpendings = $categorySpendings->sum('total_pengeluaran');

        $totalSpending = $totalWarehouseSpendings + $totalCategorySpendings;

        return [
            'pendapatan' => [
                'transaksi' => (float) $transactionIncome,
                'request' => (float) $requestIncome,
                'total' => (float) $totalIncome,
            ],
            'pengeluaran' => [
                'warehouse' => $warehouseSpendings,
                'pengeluaran_lain' => $categorySpendings,
                'total' => (float) $totalSpending,
            ],
            'laba_rugi' => (float) $totalIncome - (float) $totalSpending,
        ];
    }
}
