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
            ->with(['user', 'detailRequestStock.detailProduct.product', 'outlet', 'warehouse']);

        if (!empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        if (!empty($data['created_at_start']) && !empty($data['created_at_end'])) {
            $query->whereBetween('created_at', [$data['created_at_start'], $data['created_at_end']]);
        }

        if (!empty($data['requested_stock_min']) || !empty($data['requested_stock_max'])) {
            $query->whereHas('detailRequestStock', function ($q) use ($data) {
                if (!empty($data['requested_stock_min'])) {
                    $q->havingRaw('SUM(requested_stock) >= ?', [$data['requested_stock_min']]);
                }
                if (!empty($data['requested_stock_max'])) {
                    $q->havingRaw('SUM(requested_stock) <= ?', [$data['requested_stock_max']]);
                }
            });
        }

        if (!empty($data['warehouse_name'])) {
            $query->whereHas('warehouse', function ($q) use ($data) {
                $q->where('name', 'like', '%' . $data['warehouse_name'] . '%');
            });
        }

        // Filter kolom lainnya (outlet_id, warehouse_id, dll)
        $filteredData = array_filter($data, fn($value) => !is_null($value) && $value !== '');
        foreach ($filteredData as $index => $value) {
            if (!in_array($index, ['status', 'created_at_start', 'created_at_end', 'requested_stock_min', 'requested_stock_max', 'warehouse_name'])) {
                $query->where($index, $value);
            }
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