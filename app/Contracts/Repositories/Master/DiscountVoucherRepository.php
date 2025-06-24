<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\DiscountVoucherInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\DiscountVoucher;
use App\Models\Product;

class DiscountVoucherRepository extends BaseRepository implements DiscountVoucherInterface
{

    public function __construct(DiscountVoucher $discountVoucher)
    {
        $this->model = $discountVoucher;
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
            ->with(['store', 'details',  'details.product' => function ($query) {
                $query->select('id', 'name');
            }])
            ->where('is_delete', 0)
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->with(['store:id,name', 'details:id,variant_name,product_code,product_id', 'details.product' => function ($query) {
                $query->select('id', 'name','image');
            }])
            ->where('is_delete', 0)
            ->when($data, function ($query) use ($data) {
                if (!empty($data["search"])) {
                    $query->where(function ($q) use ($data) {
                        $q->where('name', 'like', '%' . $data["search"] . '%');
                    });
                }

                if (!empty($data["name"])) {
                    $query->where('name', 'like', '%' . $data["name"] . '%');
                }

                // if (!empty($data["variant"])) {
                //     $query->whereHas('details.varian', function ($q) use ($data) {
                //         $q->where('name', 'like', '%' . $data["variant"] . '%');
                //     });
                // }

                if (isset($data["active"])) {
                    $query->where('active', $data["active"]);
                }

                if (!empty($data["type"])) {
                    $query->where('type', $data["type"]);
                }

                if (!empty($data["discount"])) {
                    $query->where('discount', $data["discount"]);
                }

                if (!empty($data["min_discount"]) && !empty($data["max_discount"])) {
                    $query->whereBetween('discount', [$data["min_discount"], $data["max_discount"]]);
                } elseif (!empty($data["min_discount"])) {
                    $query->where('discount', '>=', $data["min_discount"]);
                } elseif (!empty($data["max_discount"])) {
                    $query->where('discount', '<=', $data["max_discount"]);
                }

                if (!empty($data["start_date"]) && !empty($data["end_date"])) {
                    $query->whereDate('start_date', '>=', $data["start_date"])
                        ->whereDate('expired', '<=', $data["end_date"]);
                } elseif (!empty($data["start_date"])) {
                    $query->whereDate('start_date', '>=', $data["start_date"]);
                } elseif (!empty($data["end_date"])) {
                    $query->whereDate('expired', '<=', $data["end_date"]);
                }

                if (!empty($data["store_id"])) {
                    $query->where('store_id', $data["store_id"]);
                }

                if (!empty($data['sort_by']) && !empty($data['sort_direction'])) {
                    $allowedSorts = ['name', 'discount', 'start_date', 'expired', 'created_at'];
                    $allowedDirections = ['asc', 'desc'];

                    $sortBy = in_array($data['sort_by'], $allowedSorts) ? $data['sort_by'] : 'created_at';
                    $sortDirection = in_array(strtolower($data['sort_direction']), $allowedDirections)
                        ? strtolower($data['sort_direction'])
                        : 'desc';

                    $query->orderBy($sortBy, $sortDirection);
                } else {
                    $query->orderBy('created_at', 'desc');
                }
            })
            ->paginate($pagination, ['*'], 'page', $page);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->with([
            'store' => function ($query) {
                $query->select('id', 'name');
            },
            'details',
            'details.product' => function ($query) {
                $query->select('id', 'name');
            }
        ])->find($id);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->with(['store', 'details', 'details.product' => function ($query) {
            $query->select('id', 'name');
        }])->where('is_delete', 0)->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        $model = $this->model->select('id', 'is_delete')->findOrFail($id);

        if ($model->is_delete) {
            return null;
        }

        $model->update($data);

        return $model->fresh();
    }

    public function delete(mixed $id): mixed
    {
        $model = $this->model->select('id', 'is_delete')->findOrFail($id);

        if ($model->is_delete) {
            return null;
        }

        $model->update(['is_delete' => 1]);

        return $model->fresh();
    }
}
