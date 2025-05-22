<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\ProductBundlingInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\ProductBundling;

class ProductBundlingRepository extends BaseRepository implements ProductBundlingInterface
{
    public function __construct(ProductBundling $model)
    {
        $this->model = $model;
    }

    public function get(): mixed
    {
        return $this->model->all();
    }

    public function store(array $data): mixed
    {
        return $this->model->create($data);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->findOrFail($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        $model = $this->show($id);
        $model->update($data);
        return $model;
    }

    public function delete(mixed $id): mixed
    {
        return $this->model->findOrFail($id)->delete();
    }

    public function restore(mixed $id): mixed
    {
        return $this->model->withTrashed()->findOrFail($id)->restore();
    }

    public function paginate(int $perPage = 10): mixed
    {
        return $this->model->paginate($perPage);
    }
}
