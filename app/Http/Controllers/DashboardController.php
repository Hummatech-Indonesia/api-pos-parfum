<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\Outlet;
use App\Models\ProductStock;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {

        $roles = auth()->user()->roles->pluck('name')->map(fn($r) => strtolower($r))->toArray();

        if (array_intersect($roles, ['warehouse', 'owner'])) {
            return $this->dashboardWarehouse();
        }

        if (array_intersect($roles, ['outlet', 'owner'])) {
            return $this->dashboardOutlet();
        }

        return response()->json(['message' => "Role tidak dikenali"], 403);
    }

    private function dashboardWarehouse()
    {
        $storeId = auth()->user()->store_id;
        $year = now()->year;

        return response()->json([
            'total_products' => Product::where('store_id', $storeId)->count(),
            'total_orders' => Transaction::where('store_id', $storeId)->count(),
            'total_retail' => Outlet::where('store_id', $storeId)->count(),
            'income_this_month' => Transaction::where('store_id', $storeId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_price'),
            'chart' => [
                'year' => $year,
                'data' => $this->getMonthlyIncome($year, $storeId)
            ],
            'recent_orders' => Transaction::with('transaction_details')
                ->where('store_id', $storeId)
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($order) => [
                    'retail_name' => $order->user_name ?? '-',
                    'product_count' => $order->transaction_details->count(),
                    'transaction_code' => $order->transaction_code,
                    'total_price' => $order->total_price,
                ])
        ]);
    }

    private function dashboardOutlet()
    {
        $storeId = auth()->user()->store_id;
        $userId = auth()->id();
        $outletId = auth()->user()->outlet_id;
        $year = now()->year;

        return response()->json([
            'total_products' => Product::where('store_id', $storeId)->count(),
            'total_orders' => Transaction::where('store_id', $storeId)
                ->where('user_id', $userId)
                ->count(),
            'income_this_month' => Transaction::where('store_id', $storeId)
                ->where('user_id', $userId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_price'),
            'chart' => [
                'year' => $year,
                'data' => $this->getMonthlyIncome($year, $storeId, $userId)
            ],
            'recent_orders' => Transaction::with('transactionDetails')
                ->where('store_id', $storeId)
                ->where('user_id', $userId)
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($order) => [
                    'retail_name' => $order->user_name ?? '-',
                    'product_count' => $order->transactionDetails?->count() ?? 0,
                    'transaction_code' => $order->transaction_code,
                    'total_price' => $order->total_price,
                ]),
            'low_stock_products' => ProductStock::with(['product', 'productDetail'])
                ->where('outlet_id', $outletId)
                ->where('stock', '<=', 10)
                ->orderBy('stock', 'asc')
                ->take(5)
                ->get()
                ->map(fn($p) => [
                    'name' => $p->product->name ?? '-',
                    'stock' => $p->stock,
                    'unit' => $p->productDetail->unit ?? '-',
                ])
        ]);
    }

    private function getMonthlyIncome($year, $storeId, $userId = null)
    {
        $query = Transaction::selectRaw('MONTH(created_at) as month, SUM(total_price) as income')
            ->whereYear('created_at', $year)
            ->where('store_id', $storeId);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $monthly = $query->groupBy('month')->pluck('income', 'month');

        return collect(range(1, 12))->map(fn($m) => (float) ($monthly[$m] ?? 0))->toArray();
    }
}
