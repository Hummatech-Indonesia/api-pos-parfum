<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\Product;

class ProductRepository extends BaseRepository implements ProductInterface
{

    public function __construct(Product $product)
    {
        $this->model = $product;
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
        ->with('store')
        ->when(count($data) > 0, function ($query) use ($data){
            foreach ($data as $index => $value){
                $query->where($index, $value);
            }
        });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        $query = $this->model->query()
            ->with([
                'store', 'category', 
                'details' => function ($q) {
                    $q->withSum('productStockOutlet','stock')->withSum('productStockWarehouse','stock');
                }
            ])
            ->withSum('details', 'stock'); // Menjumlahkan stok dari detail_product

        // Filtering berdasarkan search
        if (!empty($data["search"])) {
            $query->where('name', 'like', '%' . $data["search"] . '%');
            unset($data["search"]);
        }

        // OrderBy total stock jika param valid
        if (!empty($data["orderby_total_stock"]) && in_array($data["orderby_total_stock"], ['asc', 'desc'])) {
            $query->orderBy('details_sum_stock', $data["orderby_total_stock"]);
            unset($data["orderby_total_stock"]);
        }

        // Filtering berdasarkan parameter lainnya
        $filteredData = array_filter($data, fn($value) => !is_null($value) && $value !== '');
        foreach ($filteredData as $index => $value) {
            $query->where($index, $value);
        }

        return $query->paginate($pagination, ['*'], 'page', $page);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->with(['store' => function ($query) {
                    $query->select('id', 'name');
                },'details'])->find($id);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->with(['store','details'])->where('is_delete',0)->find($id);
    }

    public function checkActiveWithDetail(mixed $id): mixed
    {
        return $this->model->with(['store','details' => function ($query) {
            $query->with('varian', 'category')->where('is_delete',0);
        }])->whereRelation('details','is_delete', 0)->where('is_delete',0)->find($id);
    }

    public function checkActiveWithDetailV2(mixed $id): mixed
    {
        return $this->model->with(['store','details' => function ($query) {
            $query->with('varian', 'category')->where('is_delete',0);
        }])->where('is_delete',0)->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->show($id)->update($data);
    }

    public function delete(mixed $id): mixed
    {
        return $this->model->select('id')->findOrFail($id)->update(["is_delete" => 1]);
    }

}