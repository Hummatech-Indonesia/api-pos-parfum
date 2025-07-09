<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\Product;
use App\Models\ProductStock;

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
            ->with([
                'store',
                'productBundling.details',
                'details' => function ($query) {
                    $query->with('category')->withCount('transactionDetails');
                }
            ])
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
                'productBundling.details',
                'productBundling',
                'store',
                'user',
                'category',
                'details' => function ($q) {
                    $q->withCount('transactionDetails')
                        ->with([
                            'category:id,name',
                            'productStockOutlet',
                            'category',
                            'productStockWarehouse',
                            'productBundlingDetail.productDetail.productStockOutlet',
                            'productBundlingDetail.productDetail.productStockWarehouse',
                            'unitRelation',
                        ]);
                }
            ])
            ->withSum('details', 'stock');

        $user = auth()->user();

        if ($user->hasRole('outlet')) {
            $query->where('outlet_id', $user->outlet_id);
        } elseif ($user->hasRole('warehouse')) {
            $query->where('warehouse_id', $user->warehouse_id);
        }

        if (isset($data["category"])) {
            $query->where('category_id', $data["category"]);
        }

        $filteredByDetails = false;

        if (!empty($data["min_price"])) {
            $filteredByDetails = true;
            $query->whereHas('details', fn($q) => $q->where('price', '>=', $data["min_price"]));
        }

        if (!empty($data["max_price"])) {
            $filteredByDetails = true;
            $query->whereHas('details', fn($q) => $q->where('price', '<=', $data["max_price"]));
        }

        if (!empty($data['min_sales'])) {
            $filteredByDetails = true;
            $query->whereHas('details.transactionDetails', function ($q) use ($data) {
                $q->select('product_detail_id')
                    ->groupBy('product_detail_id')
                    ->havingRaw('COUNT(*) >= ?', [$data['min_sales']]);
            });
        }

        if (!empty($data['max_sales'])) {
            $filteredByDetails = true;
            $query->whereHas('details.transactionDetails', function ($q) use ($data) {
                $q->select('product_detail_id')
                    ->groupBy('product_detail_id')
                    ->havingRaw('COUNT(*) <= ?', [$data['max_sales']]);
            });
        }

        if ($user->hasRole('outlet')) {
            if (isset($data['min_stock']) || isset($data['max_stock'])) {
                $filteredByDetails = true;
                $query->whereHas('details.productStockOutlet', function ($q) use ($data) {
                    if (isset($data['min_stock'])) {
                        $q->whereRaw('COALESCE(stock, 0) >= ?', [$data['min_stock']]);
                    }
                    if (isset($data['max_stock'])) {
                        $q->whereRaw('COALESCE(stock, 0) <= ?', [$data['max_stock']]);
                    }
                });
            }
        }

        if ($user->hasRole('warehouse')) {
            if (isset($data['min_stock']) || isset($data['max_stock'])) {
                $filteredByDetails = true;
                $query->whereHas('details.productStockWarehouse', function ($q) use ($data) {
                    if (isset($data['min_stock'])) {
                        $q->whereRaw('COALESCE(stock, 0) >= ?', [$data['min_stock']]);
                    }
                    if (isset($data['max_stock'])) {
                        $q->whereRaw('COALESCE(stock, 0) <= ?', [$data['max_stock']]);
                    }
                });
            }
        }

        if (!empty($data["search"])) {
            $query->where('name', 'like', '%' . $data["search"] . '%');
        }

        if (!empty($data["orderby_total_stock"]) && in_array($data["orderby_total_stock"], ['asc', 'desc'])) {
            $query->orderBy('details_sum_stock', $data["orderby_total_stock"]);
        }

        if (!empty($data["sort_by"]) && in_array($data["sort_by"], ['name', 'created_at'])) {
            $query->orderBy($data["sort_by"], $data["sort_order"] ?? 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $filteredData = array_filter($data, fn($value) => !is_null($value) && $value !== '');
        $allowedWhereFields = ['store_id', 'category_id', 'is_delete'];
        foreach ($filteredData as $index => $value) {
            if (in_array($index, $allowedWhereFields)) {
                $query->where($index, $value);
            }
        }

        if (!$filteredByDetails) {
            $query->where(function ($q) {
                $q->whereHas('details')->orWhereDoesntHave('details');
            });
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
        $query = Product::query()
            ->where('store_id', $storeId)
            ->where('is_delete', 0);

        $user = auth()->user();

        if ($user->hasRole('outlet') && $user->outlet_id) {
            $query->where('outlet_id', $user->outlet_id);
        } elseif ($user->hasRole('warehouse') && $user->warehouse_id) {
            $query->where('warehouse_id', $user->warehouse_id);
        }

        return $query->count();
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

        $user = auth()->user();
        if ($user->hasRole('outlet')) {
            $query->where('outlet_id', $user->outlet_id);
        } elseif ($user->hasRole('warehouse')) {
            $query->where('warehouse_id', $user->warehouse_id);
        }

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

    public function getListProductWithoutBundling(array $data): mixed
    {
        return $this->model->query()
            ->with(['details', 'productBundling'])
            ->when(isset($data['store_id']), fn($q) => $q->where('store_id', $data['store_id']))
            ->when(isset($data['warehouse_id']), fn($q) => $q->where('warehouse_id', $data['warehouse_id']))
            ->when(isset($data['outlet_id']), fn($q) => $q->where('outlet_id', $data['outlet_id']))
            ->when(!empty($data['search']), fn($q) => $q->where('name', 'like', '%' . $data['search'] . '%'))
            ->whereDoesntHave('productBundling')
            ->when(!empty($data['sort_by']), function ($q) use ($data) {
                $q->orderBy($data['sort_by'], $data['sort_order'] ?? 'asc');
            })
            ->get();
    }
}
