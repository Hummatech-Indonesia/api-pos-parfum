<?php

namespace App\Contracts\Repositories\Master;

use App\Models\Unit;
use App\Contracts\Repositories\BaseRepository;
use App\Contracts\Interfaces\Master\UnitInterface;


class UnitRepository extends BaseRepository implements UnitInterface
{

    public function __construct(Unit $unit)
    {
        $this->model = $unit;
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
        $minCount = $data['min_products_count'] ?? null;
        $maxCount = $data['max_products_count'] ?? null;
        unset($data['min_products_count'], $data['max_products_count']);

        return $this->model->query()
            ->withCount(['productDetails' => function ($q) {
                $q->where('is_delete', 0);
            }])
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%')
                            ->orWhere('code', 'like', '%' . $data["search"] . '%');
                    });
                    unset($data["search"]);
                }

                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            })
            ->when(!is_null($minCount), fn($q) => $q->having('product_details_count', '>=', $minCount))
            ->when(!is_null($maxCount), fn($q) => $q->having('product_details_count', '<=', $maxCount));
    }



    public function customPaginate(int $pagination = 8, int $page = 1, ?array $data): mixed
    {
        $orderBy = $data['order_by'] ?? 'created_at';
        $orderDirection = $data['order_direction'] ?? 'desc';
        unset($data['order_by'], $data['order_direction']);

        $minCount = $data['min_products_count'] ?? null;
        $maxCount = $data['max_products_count'] ?? null;
        unset($data['min_products_count'], $data['max_products_count']);

        return $this->model->query()
            ->withCount(['productDetails' => function ($q) {
                $q->where('is_delete', 0);
            }])
            ->when($data, function ($query) use ($data) {
                if (!empty($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%')
                            ->orWhere('code', 'like', '%' . $data["search"] . '%');
                    });
                }

                if (!empty($data["start_date"])) {
                    $query->whereDate('created_at', '>=', $data["start_date"]);
                }

                if (!empty($data["end_date"])) {
                    $query->whereDate('created_at', '<=', $data["end_date"]);
                }
            })
            ->when(!is_null($minCount), fn($q) => $q->having('product_details_count', '>=', $minCount))
            ->when(!is_null($maxCount), fn($q) => $q->having('product_details_count', '<=', $maxCount))
            ->orderBy($orderBy, $orderDirection)
            ->paginate($pagination, ['*'], 'page', $page);
    }



    public function show(mixed $id): mixed
    {
        return $this->model->find($id);
    }

    public function allDataTrashed(): mixed // Untuk mencari data yang dihapus
    {
        return $this->model->withTrashed()->get();
    }

    public function update(mixed $id, array $data): mixed
    {
        $model = $this->model->select('id')->findOrFail($id);

        $model->update($data);

        return $model->fresh();
    }

    public function delete(mixed $id): mixed
    {
        $model = $this->model->select('id')->findOrFail($id);

        $model->delete();

        return $model;
    }

    public function all(): mixed
    {
        return $this->model->all();
    }

    public function cekUnit(mixed $name, mixed $code)
    {
        return $this->model->where('name', $name)->orWhere('code', $code)->first();
    }
}
