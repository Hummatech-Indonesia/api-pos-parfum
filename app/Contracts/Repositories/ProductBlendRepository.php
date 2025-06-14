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
        return $this->model->query()->with(['productBlendDetails', 'productDetail', 'warehouse:id'])->findOrFail($id);
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
        $query = $this->model->query()->with(['productBlendDetails', 'productDetail', 'warehouse:id']);

            if (isset($data["search"])) {
                $query->where(function ($q) use ($data) {
                    $q->where('date', 'like', '%' . $data["search"] . '%')
                        ->orWhereHas('productDetail', function ($q) use ($data) {
                            $q->where('material', 'like', '%' . $data["search"] . '%')
                                ->orWhere('price', 'like', '%' . $data["search"] . '%');
                        });
                })->orWhereHas('warehouse', function ($q) use ($data) {
                    $q->where('name', 'like', '%' . $data["search"] . '%');
                });
                unset($data["search"]);
            }
            return $query->paginate($pagination, ['*'], 'page', $page);
        }
}
