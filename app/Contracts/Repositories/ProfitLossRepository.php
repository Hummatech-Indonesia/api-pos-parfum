<?php

namespace App\Contracts\Repositories;

use App\Contracts\Interfaces\ProfitLossInterface;
use Illuminate\Support\Facades\DB;

final class ProfitLossRepository extends BaseRepository implements ProfitLossInterface
{
    public function getOutletProfitLoss(string $outletId, ?int $month = null, ?int $year = null): array
    {
        $month = $month ? (int) $month : null;
        $year = $year ?? now()->year;

        $pendapatan = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->where('t.outlet_id', $outletId)
            ->when($month, fn($q) => $q->whereMonth('td.created_at', $month))
            ->whereYear('td.created_at', $year)
            ->selectRaw('SUM(td.price * td.quantity) as total')
            ->value('total') ?? 0;

        $pengeluaran = DB::table('stock_request_details as srd')
            ->join('stock_requests as sr', 'srd.stock_request_id', '=', 'sr.id')
            ->where('sr.status', 'approved')
            ->where('sr.outlet_id', $outletId)
            ->when($month, fn($q) => $q->whereMonth('srd.created_at', $month))
            ->whereYear('srd.created_at', $year)
            ->selectRaw('SUM(price) as total')
            ->value('total') ?? 0;

        return [
            'pendapatan' => (float) $pendapatan,
            'pengeluaran' => (float) $pengeluaran,
            'laba_rugi' => (float) $pendapatan - (float) $pengeluaran,
        ];
    }

    public function getWarehouseProfitLoss(string $warehouseId, ?int $month = null, ?int $year = null): array
    {
        $month = $month ? (int) $month : null;
        $year = $year ?? now()->year;

        $pendapatan = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->where('t.warehouse_id', $warehouseId)
            ->when($month, fn($q) => $q->whereMonth('td.created_at', $month))
            ->whereYear('td.created_at', $year)
            ->selectRaw('SUM(td.price * td.quantity) as total')
            ->value('total') ?? 0;

        $pengeluaran = 0;

        return [
            'pendapatan' => (float) $pendapatan,
            'pengeluaran' => (float) $pengeluaran,
            'laba_rugi' => (float) $pendapatan - (float) $pengeluaran,
        ];
    }
}
