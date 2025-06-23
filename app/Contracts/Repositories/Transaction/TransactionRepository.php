<?php

namespace App\Contracts\Repositories\Transaction;

use App\Contracts\Interfaces\Transaction\TransactionInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\Transaction;
use Illuminate\Database\QueryException;

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
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('transaction_code', 'like', '%' . $data["search"] . '%');
                    });
                    unset($data["search"]);
                }

                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            })
            ->paginate($pagination, ['*'], 'page', $page);
        // ->appends(['search' => $request->search, 'year' => $request->year]);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->with('store')->find($id);
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
}
