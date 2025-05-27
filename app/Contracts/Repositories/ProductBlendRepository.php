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
        return $this->model->query()->findOrFail($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->model->query()->findOrFail($id)->update($data);
    }

    public function delete(mixed $id): mixed
    {
        return $this->model->query()->findOrFail($id)->delete();
    }

    public function customPaginate($perPage, $page, array $filters = [], array $with = [])
    {
        $query = ProductBlend::query();

        // Load relasi seperti productBlendDetails
        if (!empty($with)) {
            $query->with($with);
        }

        // Filter search
        if (!empty($filters['search'])) {
            $query->where('code', 'like', '%' . $filters['search'] . '%');
        }

        // Filter is_delete
        if (isset($filters['is_delete'])) {
            $query->where('is_delete', $filters['is_delete']);
        }

        // Filter store_id
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
