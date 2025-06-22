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
        return $this->model->query()
            ->withCount('product')
            ->with('varian', 'product.productBundling.details', 'category', 'productStockOutlet', 'productStockWarehouse')
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->withCount('product')
            ->with('varian', 'product.productBundling.details', 'category', 'productStockOutlet', 'productStockWarehouse')
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%');
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
        return $this->model->with('varian', 'product', 'category')->find($id);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->with('varian', 'product', 'category')->where('is_delete', 0)->find($id);
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
}
