<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\WarehouseInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\Warehouse;

class WarehouseRepository extends BaseRepository implements WarehouseInterface
{

    public function __construct(Warehouse $warehouse)
    {
        $this->model = $warehouse;
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
            ->with('store', 'users')
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }

    public function customPaginate(int $pagination = 8, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->with('store', 'users')
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%')
                            ->orwhere('address', 'like', '%' . $data["search"] . '%');
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
        return $this->model->with(['store' => function ($query) {
            $query->select('id', 'name');
        }, 'users' => function ($query) {
            $query->select('id', 'name', 'email');
        }])->find($id);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->with('store', 'users')->where('is_delete', 0)->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        $model = $this->model->select('id', 'is_delete')->findOrFail($id);

        if ($model->is_delete) {
            return null;
        }

        $model->update($data);

        return $model->fresh();
    }

    public function delete(mixed $id): mixed
    {
        $model = $this->model->select('id', 'is_delete')->findOrFail($id);

        if ($model->is_delete) {
            return null;
        }

        $model->update(['is_delete' => 1]);

        return $model->fresh();
    }

    public function withProductStocks($warehouseId): mixed
    {
        return $this->model->with([
            'productStocks.productDetail.product',
            'productStocks.outlet',
            'store',
            'users'
        ])->findOrFail($warehouseId);
    }

    public function getProductStocksPaginated($warehouseId, $perPage, $page): mixed
    {
        return $this->model->findOrFail($warehouseId)
            ->productStocks()
            ->with(['productDetail.product', 'outlet'])
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
