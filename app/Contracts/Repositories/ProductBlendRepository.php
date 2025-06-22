<?php

namespace App\Contracts\Repositories;

use App\Contracts\Interfaces\ProductBlendInterface;
use App\Models\ProductBlend;
use Illuminate\Support\Str;

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
        return $this->model->query()
            ->withCount('productDetail as used_product_count')
            ->findOrFail($id);
    }

    public function getDetailWithPagination(string $id, int $page = 1, int $perPage = 5)
    {
        if (!Str::isUuid($id)) {
            return ['status' => false, 'error' => 'invalid_uuid'];
        }

        $blend = $this->model
            ->select('id', 'product_detail_id', 'product_id', 'result_stock as Quantity', 'date as tanggal_pembuatan', 'created_at')
            ->with([
                'product:id,name as nama_blending',
                'productDetail:id,product_id',
            ])
            ->withCount('productBlendDetails as jumlah_bhn_baku')
            ->find($id);

        if (!$blend) {
            return ['status' => false, 'error' => 'not_found'];
        }

        $details = $blend->productBlendDetails()
            ->select('id', 'product_blend_id', 'product_detail_id', 'used_stock as quantity')
            ->with([
                'productDetail:id,product_id,variant_name',
                'productDetail.product:id,name as name_product',
            ])
            ->paginate($perPage, ['*'], 'transaction_page', $page);

        $blend->setRelation('productBlendDetails', $details);

        return ['status' => true, 'data' => $blend];
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
        $query = $this->model->query()
            ->select('id', 'product_detail_id', 'product_id', 'result_stock as quantity', 'description', 'date', 'created_at')
            ->with([
                'productDetail:id,product_id',
                'product:id,name as blend_name',
                'productBlendDetails:id,product_blend_id,product_detail_id,used_stock,created_at',
                'productBlendDetails.productDetail:id,variant_name,product_id',
                'productBlendDetails.productDetail.product:id,name',
            ])
            ->withCount('productBlendDetails as used_product_count');

        if (isset($data["search"])) {
            $search = $data["search"];
            $query->where(function ($q) use ($search) {
                $q->where('date', 'like', '%' . $search . '%')
                    ->orWhereHas('productDetail', function ($q2) use ($search) {
                        $q2->where('material', 'like', '%' . $search . '%')
                            ->orWhere('price', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('warehouse', function ($q3) use ($search) {
                        $q3->where('name', 'like', '%' . $search . '%');
                    });
            });

            unset($data["search"]);
        }

        if (!empty($data)) {
            foreach ($data as $field => $value) {
                $query->where($field, $value);
            }
        }

        return $query->paginate($pagination, ['*'], 'page', $page);
    }
}
