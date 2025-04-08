<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\StockRequestDetailInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\StockRequestDetail;

class StockRequestDetailRepository extends BaseRepository implements StockRequestDetailInterface
{

    public function __construct(StockRequestDetail $stockRequestDetail)
    {
        $this->model = $stockRequestDetail;
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
        ->when(count($data) > 0, function ($query) use ($data){
            foreach ($data as $index => $value){
                $query->where($index, $value);
            }
        });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        $query = $this->model->query();

        // Filtering berdasarkan parameter lainnya
        $filteredData = array_filter($data, fn($value) => !is_null($value) && $value !== '');
        foreach ($filteredData as $index => $value) {
            $query->where($index, $value);
        }

        return $query->paginate($pagination, ['*'], 'page', $page);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->show($id)->update($data);
    }

    public function delete(mixed $id): mixed
    {
        return $this->show($id)->update(["is_delete" => 1]);
    }

}