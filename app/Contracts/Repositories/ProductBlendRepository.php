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
            ->with([
                'product',
            ])
            ->withCount('productBlendDetails as jumlah_bhn_baku')
            ->find($id);

        if (!$blend) {
            return ['status' => false, 'error' => 'not_found'];
        }

        $details = $blend->productBlendDetails()
            ->with([
                'productDetail',
                'productDetail.product',
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

    public function customQuery(array $data): mixed
    {
        return $this->model->query()
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            })->with([
                'productDetail',
                'product',
                'productBlendDetails',
                'productBlendDetails.productDetail',
                'productBlendDetails.productDetail.product',
            ])
            ->withCount('productBlendDetails as used_product_count');
    }

    public function getByIds(array $ids)
    {
        return $this->model
            ->whereIn('id', $ids)
            ->with([
                'productDetail:id,product_id,variant_name',
                'product:id,name',
                'productBlendDetails:id,product_blend_id,product_detail_id,used_stock',
                'productBlendDetails.productDetail:id,variant_name,product_id',
                'productBlendDetails.productDetail.product:id,name',
            ])
            ->withCount('productBlendDetails as used_product_count')
            ->get();
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        $orderBy = $data['order_by'] ?? 'created_at';
        $orderDirection = $data['order_direction'] ?? 'desc';
        unset($data['order_by'], $data['order_direction']);

        return $this->model->query()
            ->with([
                'productDetail',
                'product',
                'productBlendDetails',
                'productBlendDetails.productStock',
                'productBlendDetails.productDetail',
                'productBlendDetails.productDetail.product',
            ])
            ->withCount('productBlendDetails as used_product_count')
            ->where('store_id', auth()->user()->store_id)
            ->when($data, function ($query) use ($data) {
                if (!empty($data["search"])) {
                    $query->where(function ($q) use ($data) {
                        $q->where('date', 'like', '%' . $data["search"] . '%');
                    });
                }

                if (!empty($data["date"])) {
                    $query->where('date', 'like', '%' . $data["date"] . '%');
                }

                if (!empty($data["description"])) {
                    $query->where('description', 'like', '%' . $data["description"] . '%');
                }

                if (!empty($data["productDetail"])) {
                    $query->whereHas('productDetail', function ($q) use ($data) {
                        $q->where('variant_name', 'like', '%' . $data["productDetail"] . '%');
                    });
                }

                if (!empty($data['start_date'])) {
                    $query->whereDate('date', '>=', $data['start_date']);
                }

                if (!empty($data['end_date'])) {
                    $query->whereDate('date', '<=', $data['end_date']);
                }

                // Filter quantity (hasil blend)
                if (!empty($data['min_quantity'])) {
                    $query->where('result_stock', '>=', $data['min_quantity']);
                }

                if (!empty($data['max_quantity'])) {
                    $query->where('result_stock', '<=', $data['max_quantity']);
                }
            })
            ->orderBy($orderBy, $orderDirection)
            ->paginate($pagination, ['*'], 'page', $page);
    }
}
