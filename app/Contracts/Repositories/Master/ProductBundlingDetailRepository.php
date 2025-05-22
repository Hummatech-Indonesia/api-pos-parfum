<?php 

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\ProductBundlingDetailInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\ProductBundlingDetail;

class ProductBundlingDetailRepository extends BaseRepository implements ProductBundlingDetailInterface 
{
    public function __construct(ProductBundlingDetail $model)
    {
        $this->model = $model;
    }

    public function get(): mixed
    {
        return $this->model->get();
    }

    public function store(array $data): mixed
    {
        return $this->model->create($data);
    }

    public function show($id): mixed
    {
        return $this->model->withTrashed()->findOrFail($id);
    }


    public function update(mixed $id, array $data): mixed
    {
        $model = $this->show($id);
        $model->update($data);
        return $model;
    }

    public function delete(mixed $id): mixed
    {
        return $this->show($id)->delete();
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