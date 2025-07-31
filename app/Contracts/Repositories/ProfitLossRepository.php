<?php

namespace App\Contracts\Repositories;

use App\Contracts\Interfaces\ProfitLossInterface;
use App\Enums\StockRequestStatus;
use App\Enums\Transactionstatus;
use App\Models\Pengeluaran;
use App\Models\StockRequest;
use App\Models\Transaction;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

final class ProfitLossRepository extends BaseRepository implements ProfitLossInterface
{
    public function getOutletProfitLoss(string $outlet_id, ?int $month = null, ?int $year = null): array
    {
        $income = Transaction::where('outlet_id', $outlet_id)
            ->selectRaw('SUM(amount_price) as amount')
            ->where('transaction_status', Transactionstatus::COMPLETE)
            ->when($month, fn($q) => $q->whereMonth('payment_time', $month))
            ->when($year, fn($q) => $q->whereYear('payment_time', $year))
            ->get()
            ->map(fn($item) => [
                'name' => $item->transaction_code ?? null,
                'amount' => (float) $item->amount,
            ]);

        $requestSpending = StockRequest::with('detailProduct')
            ->where('outlet_id', $outlet_id)
            ->selectRaw('SUM(total_price) as amount')
            ->where('status', StockRequestStatus::APPROVED)
            ->when($month, fn($q) => $q->whereMonth('created_at', $month))
            ->when($year, fn($q) => $q->whereYear('created_at', $year))
            ->get()
            ->map(fn($item) => [
                'name' => $item->detailProduct?->product?->name ?? null,
                'amount' => (float) $item->amount,
            ]);

        $categorySpendings = Pengeluaran::with('kategori_pengeluaran')
            ->selectRaw('SUM(nominal_pengeluaran) as amount')
            ->where('outlet_id', $outlet_id)
            ->when($month, fn($q) => $q->whereMonth('tanggal_pengeluaran', $month))
            ->when($year, fn($q) => $q->whereYear('tanggal_pengeluaran', $year))
            ->get()
            ->map(fn($item) => [
                'name' => $item->kategori_pengeluaran?->nama ?? null,
                'amount' => (float) $item->amount,
            ]);

        $pengeluaran = $requestSpending->merge($categorySpendings)->values();

        return [
            'pendapatan' => $income,
            'pengeluaran' => $pengeluaran,
            'total' => (float) $income->sum('amount') - $pengeluaran->sum('amount'),
        ];
    }

    public function getWarehouseProfitLoss(string $warehouse_id, ?int $month = null, ?int $year = null): array
    {
        $transactionIncome = Transaction::where('warehouse_id', $warehouse_id)
            ->selectRaw('SUM(amount_price) as amount')
            ->where('transaction_status', Transactionstatus::COMPLETE)
            ->when($month, fn($q) => $q->whereMonth('payment_time', $month))
            ->when($year, fn($q) => $q->whereYear('payment_time', $year))
            ->get()
            ->map(fn($item) => [
                'name' => $item->transaction_code ?? null,
                'amount' => (float) $item->amount,
            ]);

        $requestIncome = StockRequest::with('detailProduct')
            ->where('warehouse_id', $warehouse_id)
            ->selectRaw('SUM(total_price) as amount')
            ->where('status', StockRequestStatus::APPROVED)
            ->when($month, fn($q) => $q->whereMonth('created_at', $month))
            ->when($year, fn($q) => $q->whereYear('created_at', $year))
            ->get()
            ->map(fn($item) => [
                'name' => $item->detailProduct?->product?->name ?? null,
                'amount' => (float) $item->amount,
            ]);

        $pendapatan = $transactionIncome->merge($requestIncome)->values();

        $warehouseSpendings = WarehouseStock::with('productDetail')
            ->selectRaw('SUM(total_price) as amount')
            ->when($month, fn($q) => $q->whereMonth('tanggal_pengeluaran', $month))
            ->when($year, fn($q) => $q->whereYear('tanggal_pengeluaran', $year))
            ->get()
            ->map(fn($item) => [
                'name' => $item->productDetail?->product?->name ?? null,
                'amount' => (float) $item->amount,
            ]);

        $categorySpendings = Pengeluaran::with('kategori_pengeluaran')
            ->selectRaw('SUM(nominal_pengeluaran) as amount')
            ->where('warehouse_id', $warehouse_id)
            ->when($month, fn($q) => $q->whereMonth('tanggal_pengeluaran', $month))
            ->when($year, fn($q) => $q->whereYear('tanggal_pengeluaran', $year))
            ->get()
            ->map(fn($item) => [
                'name' => $item->kategori_pengeluaran?->nama ?? null,
                'amount' => (float) $item->amount,
            ]);

        $pengeluaran = $warehouseSpendings->merge($categorySpendings)->values();

        return [
            'pendapatan' => $pendapatan,
            'pengeluaran' => $pengeluaran,
            'total' => (float) $pendapatan->sum('amount') - $pengeluaran->sum('amount'),
        ];
    }
}
