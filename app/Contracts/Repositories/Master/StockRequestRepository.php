<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\StockRequestInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\StockRequest;

class StockRequestRepository extends BaseRepository implements StockRequestInterface
{

    public function __construct(StockRequest $stockRequest)
    {
        $this->model = $stockRequest;
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
        ->with(['user', 'detailProduct', 'outlet', 'warehouse'])
        ->when(count($data) > 0, function ($query) use ($data){
            foreach ($data as $index => $value){
                $query->where($index, $value);
            }
        });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        $query = $this->model->query()
            ->with(['user', 'detailRequestStock.detailProduct.product', 'outlet' , 'warehouse']);

        // Filtering berdasarkan parameter lainnya
        $filteredData = array_filter($data, fn($value) => !is_null($value) && $value !== '');
        foreach ($filteredData as $index => $value) {
            $query->where($index, $value);
        }

        return $query->paginate($pagination, ['*'], 'page', $page);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->with('store')->find($id);
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