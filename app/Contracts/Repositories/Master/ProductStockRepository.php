<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\ProductStock;

class ProductStockRepository extends BaseRepository implements ProductStockInterface
{

    public function __construct(ProductStock $productStock)
    {
        $this->model = $productStock;
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
        return $this->model->query()
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
        return $this->model->find($id);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->where('is_delete',0)->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->show($id)->update($data);
    }

    public function delete(mixed $id): mixed
    {
        return $this->show($id)->update(["is_delete" => 1]);
    }

    public function getFromProductDetail(mixed $product_detail_id) {
        return $this->model->where('warehouse_id', auth()->user()->warehouse_id)
                    ->where('product_detail_id', $product_detail_id)
                    ->first();
    }

    public function checkStock(mixed $product_detail_id)
    {   
        return $this->model->where('warehouse_id', auth()->user()->warehouse_id)->where('product_detail_id', $product_detail_id)->first();
    }

    public function checkNewStock(mixed $product_detail_id, mixed $product_id)
    {
        // return $this->model->firstOrNew([
        //                 'warehouse_id' => auth()->user()->warehouse_id,
        //                 'product_detail_id' => $product_detail_id,
        //                 'product_id' => $product_id,
        //             ]);
        return $this->model->where('warehouse_id', auth()->user()->warehouse_id)->where('product_detail_id', $product_detail_id)->where('product_id', $product_id)->first();
    }
}