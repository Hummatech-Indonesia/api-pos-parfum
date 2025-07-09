<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\ProductBundlingInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\ProductBundling;

use function PHPUnit\Framework\isEmpty;

class ProductBundlingRepository extends BaseRepository implements ProductBundlingInterface
{
    public function __construct(ProductBundling $model)
    {
        $this->model = $model;
    }

    public function get(): mixed
    {
        return $this->model->all();
    }

    public function store(array $data): mixed
    {
        $created = $this->model->create($data);
        return $this->model->with('details')->find($created->id);
    }


    public function show(mixed $id): mixed
    {
        return $this->model->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        $model = $this->show($id);
        $model->update($data);
        return $model;
    }

    public function delete(mixed $id): mixed
    {
        return $this->model->findOrFail($id)->delete();
    }

    public function restore(mixed $id): mixed
    {
        return $this->model->withTrashed()->findOrFail($id)->restore();
    }

    public function paginate(int $perPage = 10): mixed
    {
        return $this->model->paginate($perPage);
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->with(['product', 'category', 'details.unitRelation'])
            ->when(auth()->check(), function ($query) {
                $user = auth()->user();

                if ($user->hasRole('outlet')) {
                    $query->where('outlet_id', $user->outlet_id);
                } elseif ($user->hasRole('warehouse')) {
                    $query->where('warehouse_id', $user->warehouse_id);
                }
            })
            ->when(!empty($data), function ($query) use ($data) {
                if (!empty($data['search'])) {
                    $query->where(function ($q) use ($data) {
                        $q->where('name', 'like', '%' . $data['search'] . '%');
                    });
                }

                if (!empty($data['name'])) {
                    $query->where('name', 'like', '%' . $data['name'] . '%');
                }

                if (!empty($data['category'])) {
                    $query->whereHas('category', function ($q) use ($data) {
                        $q->where('name', 'like', '%' . $data['category'] . '%');
                    });
                }

                if (!empty($data['product'])) {
                    $query->whereHas('product', function ($q) use ($data) {
                        $q->where('name', 'like', '%' . $data['product'] . '%');
                    });
                }

                if (!empty($data['created_from']) && !empty($data['created_to'])) {
                    $query->whereBetween('created_at', [
                        $data['created_from'] . ' 00:00:00',
                        $data['created_to'] . ' 23:59:59'
                    ]);
                } elseif (!empty($data['created_from'])) {
                    $query->where('created_at', '>=', $data['created_from'] . ' 00:00:00');
                } elseif (!empty($data['created_to'])) {
                    $query->where('created_at', '<=', $data['created_to'] . ' 23:59:59');
                }

                if (!empty($data['min_stock'])) {
                    $query->where('stock', '>=', $data['min_stock']);
                }

                if (!empty($data['max_stock'])) {
                    $query->where('stock', '<=', $data['max_stock']);
                }

                if (!empty($data['min_price'])) {
                    $query->where('price', '>=', $data['min_price']);
                }

                if (isset($data['max_price'])) {
                    $query->where('price', '<=', $data['max_price']);
                }

                if (!empty($data['status'])) {
                    if ($data['status'] === 'active') {
                        $query->where('stock', '>', 0);
                    } elseif ($data['status'] === 'non-active') {
                        $query->where('stock', '<=', 0);
                    }
                }

                if (isset($data['min_material']) || isset($data['max_material'])) {
                    $query->withCount('details');
                    if (isset($data['min_material'])) {
                        $query->having('details_count', '>=', $data['min_material']);
                    }
                    if (isset($data['max_material'])) {
                        $query->having('details_count', '<=', $data['max_material']);
                    }
                }
            })
            ->paginate($pagination, ['*'], 'page', $page);
    }


    public function customQuery(array $data): mixed
    {
        return $this->model->query()
            ->with('product', 'category', 'details',  'details.unitRelation')
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }
}
