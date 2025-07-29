<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\WarehouseStockInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\WarehouseStock;
use Illuminate\Support\Collection;

class WarehouseStockRepository extends BaseRepository implements WarehouseStockInterface
{

    public function __construct(WarehouseStock $warehouseStock)
    {
        $this->model = $warehouseStock;
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
            ->with('productDetail.product')
            ->when(auth()->user()?->warehouse_id, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('warehouse_id', auth()->user()->warehouse_id);
                });
            })
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->whereHas('productDetail.product', function ($q) use ($data) {
                            $q->where('name', 'like', '%' . $data["search"] . '%');
                        });
                    });
                    unset($data["search"]);
                }
                if (!empty($data["from_date"])) {
                    $query->where('created_at', '>=', $data["from_date"]);
                }
                if (!empty($data["until_date"])) {
                    $query->where('created_at', '<=', $data["until_date"]);
                }

                if (!empty($data["min_stock"])) {
                    $query->where('stock', '>=', $data["min_stock"]);
                }

                if (!empty($data["max_stock"])) {
                    $query->where('stock', '<=', $data["max_stock"]);
                }

                if (!empty($data['sort_by']) && !empty($data['sort_direction'])) {
                    $allowedSorts = ['created_at', 'updated_at'];
                    $allowedDirections = ['asc', 'desc'];

                    $sortBy = in_array($data['sort_by'], $allowedSorts) ? $data['sort_by'] : 'updated_at';
                    $sortDirection = in_array(strtolower($data['sort_direction']), $allowedDirections)
                        ? strtolower($data['sort_direction'])
                        : 'desc';

                    $query->orderBy($sortBy, $sortDirection);
                } else {
                    $query->orderBy('updated_at', 'desc');
                }
            })
            ->paginate($pagination, ['*'], 'page', $page);
        // ->appends(['search' => $request->search, 'year' => $request->year]);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->find($id);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->where('is_delete', 0)->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->show($id)->update($data);
    }

    public function delete(mixed $id): mixed
    {
        return $this->show($id)->update(["is_delete" => 1]);
    }


    public function getAll(?string $date = null): Collection
    {
        return $this->model
            ->with(['productDetail.product', 'unit'])
            ->when($date, function ($query) use ($date) {
                $query->where('created_at', '=', $date);
            })
            ->orderBy('created_at')
            ->get();
    }

    public function getTotalExpenditure(): mixed
    {
        return $this->model
            ->selectRaw('
            DATE_FORMAT(created_at, "%Y-%m") as bulan,
            COALESCE(SUM(total_price), 0) as total_pengeluaran
        ')
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->orderBy('bulan', 'desc')
            ->get();
    }

    public function getSpendingByDate(): mixed
    {
        $data = $this->model
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn($item) => \Carbon\Carbon::parse($item->created_at)->format('Y-m-d'));

        $result = [];

        foreach ($data as $date => $items) {
            $result[] = [
                'tanggal' => $date,
                'total_data' => $items->count(),
                'total_pengeluaran' => $items->sum('total_price'),
                'data' => $items->values()
            ];
        }

        return $result;
    }
}
