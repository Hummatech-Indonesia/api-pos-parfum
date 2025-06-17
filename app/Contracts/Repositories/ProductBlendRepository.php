<?php

namespace App\Contracts\Repositories;

use App\Contracts\Interfaces\ProductBlendInterface;
use App\Models\ProductBlend;

class ProductBlendRepository extends BaseRepository implements ProductBlendInterface
{
    public function __construct(ProductBlend $ProductBlend)
    {
        $this->model = $ProductBlend;
    }

    public function get(): mixed
    {
        return $this->model->query()->get();
    }

    public function store(array $data): mixed
    {
        return $this->model->query()->create($data);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->query()
        ->withCount('productDetail as used_product_count')
        ->findOrFail($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->model->query()->findOrFail($id)->update($data);
    }

    public function delete(mixed $id): mixed
    {
        return $this->model->query()->findOrFail($id)->delete();
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        $query = $this->model->query()->with([
            'productBlendDetails.productDetail:id,product_id',
            'productBlendDetails.productDetail.product:id,name',
            'warehouse:id',
        ])
        ->withCount('productDetail as used_product_count');

        if (isset($data["search"])) {
            $search = $data["search"];
            $query->where(function ($q) use ($search) {
                $q->where('date', 'like', '%' . $search . '%')
                    ->orWhereHas('productDetail', function ($q2) use ($search) {
                        $q2->where('material', 'like', '%' . $search . '%')
                            ->orWhere('price', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('warehouse', function ($q3) use ($search) {
                        $q3->where('name', 'like', '%' . $search . '%');
                    });
            });

            unset($data["search"]);
        }

        if (!empty($data)) {
            foreach ($data as $field => $value) {
                $query->where($field, $value);
            }
        }

        return $query->paginate($pagination, ['*'], 'page', $page);
    }
}
