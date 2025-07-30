<?php

namespace App\Contracts\Repositories\Transaction;

use App\Contracts\Interfaces\Transaction\TransactionInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionRepository extends BaseRepository implements TransactionInterface
{
    public function __construct(Transaction $transaction)
    {
        $this->model = $transaction;
    }

    public function get(): mixed
    {
        return $this->model->get();
    }

    public function store(array $data): mixed
    {
        return $this->model->create($data);
    }

    public function customQuery(array $data): mixed
    {
        return $this->model->query()
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->withCount('transaction_details as quantity')
            ->when(count($data) > 0, function ($query) use ($data) {
                if (!empty($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('transaction_code', 'like', '%' . $data["search"] . '%');
                    });
                    unset($data["search"]);
                }

                if (!empty($data['min_price'])) {
                    $query->where('amount_price', '>=', $data['min_price']);
                }

                if (!empty($data['max_price'])) {
                    $query->where('amount_price', '<=', $data['max_price']);
                }

                if (!empty($data['start_date'])) {
                    $query->whereDate('payment_time', '>=', $data['start_date']);
                }

                if (!empty($data['end_date'])) {
                    $query->whereDate('payment_time', '<=', $data['end_date']);
                }

                if (!empty($data['min_quantity'])) {
                    $query->having('quantity', '>=', $data['min_quantity']);
                }

                if (!empty($data['max_quantity'])) {
                    $query->having('quantity', '<=', $data['max_quantity']);
                }
            })
            ->paginate($pagination, ['*'], 'page', $page);
        // ->appends(['search' => $request->search, 'year' => $request->year]);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->with(['store', 'cashier', 'user', 'warehouse', 'outlet'])->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->show($id)->update($data);
    }

    public function countByStore(string $storeId): int
    {
        return Transaction::where('store_id', $storeId)->count();
    }

    public function countByUser(string $storeId, string $userId): int
    {
        return Transaction::where('store_id', $storeId)->where('user_id', $userId)->count();
    }

    public function sumThisMonth(string $storeId, string $userId = null): float
    {
        $query = Transaction::where('store_id', $storeId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->sum('total_price');
    }

    public function monthlyIncome($year, $storeId, $userId = null): array
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

    public function recentOrdersByStore(string $storeId)
    {
        return Transaction::with('transaction_details')
            ->where('store_id', $storeId)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($order) => [
                'retail_name' => $order->user_name ?? '-',
                'product_count' => $order->transaction_details->count(),
                'transaction_code' => $order->transaction_code,
                'total_price' => $order->total_price,
            ]);
    }

    public function recentOrdersByUser(string $storeId, string $userId)
    {
        return Transaction::with('transaction_details')
            ->where('store_id', $storeId)
            ->where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($order) => [
                'retail_name' => $order->user_name ?? '-',
                'product_count' => $order->transaction_details->count(),
                'transaction_code' => $order->transaction_code,
                'total_price' => $order->total_price,
            ]);
    }

    public function getDataForExport(array $filters)
    {
        $query = $this->model->query()
            ->with(['store', 'outlet', 'user', 'transaction_details'])
            ->withCount('transaction_details');

        if ($outletId = Auth::user()?->outlet_id ?? Auth::user()?->outlet?->id) {
            $query->where('outlet_id', $outletId);
        }

        if (!empty($filters["search"])) {
            $query->where(function ($q) use ($filters) {
                $q->where('payment_time', 'like', '%' . $filters["search"] . '%');
            });
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('payment_time', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('payment_time', '<=', $filters['end_date']);
        }

        return $query->get();
    }

    public function getSummary(?string $warehouse_id, ?string $outlet_id, ?int $month = null, ?int $year = null): mixed
    {
        $query = $this->model->query();

        if ($warehouse_id) {
            $query->where('warehouse_id', $warehouse_id);
        }

        if ($outlet_id) {
            $query->where('outlet_id', $outlet_id);
        }
        
        if ($month) {
            $query->whereMonth('payment_time', $month);
        }

        if ($year) {
             $query->whereYear('payment_time', $year);
        }

        $transactions = $query
            ->orderBy('payment_time', 'desc')
            ->get();

        $summary = [
            'total_transaksi' => $transactions->count(),
            'total_nominal' => $transactions->sum('amount_price'),
            'terakhir_transaksi' => optional(Carbon::parse($transactions->max('payment_time')))->format('Y-m-d H:i:s'),
            'data' => $transactions,
        ];

        return $summary;
    }

    public function getTotalIncome(?string $outlet_id = null, ?string $warehouse_id = null): int
    {
        $query = $this->model->where('transaction_status', TransactionStatus::COMPLETE);

        if ($outlet_id) {
            $query->where('outlet_id', $outlet_id);
        }

        if ($warehouse_id) {
            $query->where('warehouse_id', $warehouse_id);
        }

        return (int) $query->sum('amount_price');
    }

    public function getMonthlyIncome(): mixed
    {
        return $this->model
            ->selectRaw('
            DATE_FORMAT(payment_time, "%Y-%m") as bulan,
            SUM(amount_price) as total_pendapatan
        ')
            ->where('transaction_status', TransactionStatus::COMPLETE)
            ->groupBy(DB::raw('DATE_FORMAT(payment_time, "%Y-%m")'))
            ->orderBy('bulan', 'desc')
            ->get();
    }

    public function getTransactionByDate(): mixed
    {
        return $this->model
            ->selectRaw('DATE(payment_time) as date, COUNT(*) as total_transaksi, SUM(amount_price) as total_nominal')
            ->where('transaction_status', TransactionStatus::COMPLETE)
            ->groupBy('date')
            ->orderByDesc('date')
            ->get();
    }
}
