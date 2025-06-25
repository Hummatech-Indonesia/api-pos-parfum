<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\Product;

class ProductRepository extends BaseRepository implements ProductInterface
{

    public function __construct(Product $product)
    {
        $this->model = $product;
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
            ->with(['store', 'details' => function ($query) {
                $query->with('category')->withCount('transactionDetails');
            }
            ])
            ->with([
                'store', 'productBundling.details',
                'details' => function ($query) {
                $query->with('varian', 'category')->withCount('transactionDetails');
            }])
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    if (in_array($index, ['search', 'sort_by', 'sort_order', 'orderby_total_stock'])) continue;
                    $query->where($index, $value);
                }
            });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        $query = $this->model->query()
            ->with([
                'store',
                'category',
                'details' => function ($q) {
                    $q->withCount('transactionDetails')
                    ->with(['category:id,name'])
                    ->withSum('productStockOutlet', 'stock')
                    ->withSum('productStockWarehouse', 'stock');
                }
            ])
            ->withSum('details', 'stock');

        if (!empty($data["search"])) {
            $query->where('name', 'like', '%' . $data["search"] . '%');
            unset($data["search"]);
        }

        if (!empty($data["orderby_total_stock"]) && in_array($data["orderby_total_stock"], ['asc', 'desc'])) {
            $query->orderBy('details_sum_stock', $data["orderby_total_stock"]);
            unset($data["orderby_total_stock"]);
        }

        if (!empty($data["sort_by"]) && in_array($data["sort_by"], ['name', 'created_at'])) {
            $query->orderBy($data["sort_by"], $data["sort_order"] ?? 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }


        $filteredData = array_filter($data, fn($value) => !is_null($value) && $value !== '');
        foreach ($filteredData as $index => $value) {
            if (!in_array($index, ['sort_by', 'sort_order'])) {
                $query->where($index, $value);
            }
        }

        return $query->paginate($pagination, ['*'], 'page', $page);
    }


    public function show(mixed $id): mixed
    {
        return $this->model->with(['store' => function ($query) {
            $query->select('id', 'name');
        }, 'details'])->find($id);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->with(['store', 'details'])->where('is_delete', 0)->find($id);
    }

    public function checkActiveWithDetail(mixed $id): mixed
    {
        return $this->model->with(['store', 'details' => function ($query) {
            $query->with('category')->withCount('transactionDetails')->where('is_delete', 0);
        }])->whereRelation('details', 'is_delete', 0)->where('is_delete', 0)->find($id);
    }

    public function checkActiveWithDetailV2(mixed $id): mixed
    {
        return $this->model->with(['store', 'details' => function ($query) {
            $query->with('category')->withCount('transactionDetails');
        }])->where('is_delete', 0)->find($id);
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

        $model->details()->update(['is_delete' => 1]);
        $model->update(['is_delete' => 1]);

        return $model->fresh();
    }

    public function countByStore(string $storeId): int
    {
        return Product::where('store_id', $storeId)->where('is_delete', 0)->count();
    }

    public function getListProduct(array $filters = []): mixed
    {
        $query = $this->model->query()
            ->with(['details' => function ($q) {
                $q->where('is_delete', 0)
                    ->withCount('transactionDetails')
                    ->with(['category'])
                    ->withSum('productStockOutlet', 'stock')
                    ->withSum('productStockWarehouse', 'stock');
            }])
            ->with('category')
            ->withSum('details', 'stock');

        if (!empty($filters["search"])) {
            $query->where('name', 'like', '%' . $filters["search"] . '%');
        }

        if (!empty($filters["sort_by"])) {
            $query->orderBy($filters["sort_by"], $filters["sort_order"] ?? 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $query->where('is_delete', $filters['is_delete'] ?? 0);

        if (isset($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        return $query->get();
    }

}
