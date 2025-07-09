<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\ProductDetail;

class ProductDetailRepository extends BaseRepository implements ProductDetailInterface
{

    public function __construct(ProductDetail $productDetail)
    {
        $this->model = $productDetail;
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
        $user = auth()->user();

        return $this->model->query()
            ->withCount('product')
            ->with('product.productBundling.details', 'category', 'productStockOutlet', 'productStockWarehouse')
            ->with('product.productBundling.details', 'category', 'productStockOutlet', 'productStockWarehouse', 'unitRelasi:id,name')
            ->when(isset($data['store_id']), function ($query) use ($data) {
                $query->whereHas('product', fn($q) => $q->where('store_id', $data['store_id']));
            })
            ->when(isset($data['product_id']), function ($query) use ($data) {
                $query->where('product_id', $data['product_id']);
            })
            ->when($user->hasRole('outlet'), function ($query) use ($user) {
                $query->whereHas('product', fn($q) => $q->where('outlet_id', $user->outlet_id));
            })
            ->when($user->hasRole('warehouse'), function ($query) use ($user) {
                $query->whereHas('product', fn($q) => $q->where('warehouse_id', $user->warehouse_id));
            })

            ->when(isset($data['search']), function ($query) use ($data) {
                $query->where(function ($q) use ($data) {
                    $q->where('name', 'like', '%' . $data["search"] . '%');
                });
            })

            ->when(!empty($data['sort_by']) && !empty($data['sort_direction']), function ($query) use ($data) {
                $allowedSorts = ['name', 'category', 'created_at', 'updated_at'];
                $allowedDirections = ['asc', 'desc'];

                $sortBy = in_array($data['sort_by'], $allowedSorts) ? $data['sort_by'] : 'updated_at';
                $sortDir = in_array(strtolower($data['sort_direction']), $allowedDirections) ? strtolower($data['sort_direction']) : 'desc';

                $query->orderBy($sortBy, $sortDir);
            }, function ($query) {
                $query->orderBy('updated_at', 'desc');
            })

            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $key => $value) {
                    if (!in_array($key, ['search', 'sort_by', 'sort_direction', 'store_id', 'warehouse_id', 'outlet_id', 'product_id'])) {
                        $query->where($key, $value);
                    }
                }
            })

            ->where('is_delete', 0);
    }


    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->withCount('transactionDetails')
            ->withCount('product')
            ->with('product.productBundling.details', 'category', 'productStockOutlet', 'productStockWarehouse', 'product', 'unitRelasi')
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%');
                    });
                    unset($data["search"]);
                }

                $user = auth()->user();

                if ($user->hasRole('outlet')) {
                    $query->whereHas('product', function ($q) use ($user) {
                        $q->where('outlet_id', $user->outlet_id);
                    });
                } elseif ($user->hasRole('warehouse')) {
                    $query->whereHas('product', function ($q) use ($user) {
                        $q->where('warehouse_id', $user->warehouse_id);
                    });
                }

                if (isset($data['store_id'])) {
                    $query->whereHas('product', function ($q) use ($data) {
                        $q->where('store_id', $data['store_id']);
                    });
                }

                if (!empty($data['sort_by']) && !empty($data['sort_direction'])) {
                    $allowedSorts = ['name', 'category', 'created_at', 'updated_at'];
                    $allowedDirections = ['asc', 'desc'];

                    $sortBy = in_array($data['sort_by'], $allowedSorts) ? $data['sort_by'] : 'updated_at';
                    $sortDirection = in_array(strtolower($data['sort_direction']), $allowedDirections)
                        ? strtolower($data['sort_direction'])
                        : 'desc';

                    $query->orderBy($sortBy, $sortDirection);
                } else {
                    $query->orderBy('updated_at', 'desc');
                }

                foreach ($data as $index => $value) {
                    if (!in_array($index, ['search', 'sort_by', 'sort_direction', 'store_id', 'warehouse_id', 'outlet_id', 'product_id'])) {
                        $query->where($index, $value);
                    }
                }
            })


            ->paginate($pagination, ['*'], 'page', $page);
        // ->appends(['search' => $request->search, 'year' => $request->year]);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->with('product', 'category')->find($id);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->with('product', 'category')->where('is_delete', 0)->find($id);
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

    public function find(string $id)
    {
        return ProductDetail::find($id);
    }

    public function findWithProduct(string $id)
    {
        return ProductDetail::with('product')->find($id);
    }
}
