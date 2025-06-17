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
        ->with('store','users')
        ->when(count($data) > 0, function ($query) use ($data){
            foreach ($data as $index => $value){
                $query->where($index, $value);
            }
        });
    }

    public function customPaginate(int $pagination = 8, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
        ->with('store','users')
        ->when(count($data) > 0, function ($query) use ($data){
            if(isset($data["search"])){
                $query->where(function ($query2) use ($data) {
                    $query2->where('name', 'like', '%' . $data["search"] . '%')
                    ->orwhere('address', 'like', '%' . $data["search"] . '%');
                });
                unset($data["search"]);
            }

            foreach ($data as $index => $value){
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
                } ,'users' => function ($query) {
                    $query->select('id', 'name', 'email');
                }])->find($id);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->with('store','users')->where('is_delete',0)->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->show($id)->update($data);
    }

    public function delete(mixed $id): mixed
    {
        return $this->model->select('id')->show($id)->update(["is_delete" => 1]);
    }

}