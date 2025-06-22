<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\OutletInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\Outlet;

class OutletRepository extends BaseRepository implements OutletInterface
{

    public function __construct(Outlet $outlet)
    {
        $this->model = $outlet;
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
            ->with('store', 'users')
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }

    public function customPaginate(int $pagination = 8, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->with('store:id,name', 'users')
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%')
                            ->orwhere('address', 'like', '%' . $data["search"] . '%');
                    });
                    unset($data["search"]);
                }

                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            })
            ->paginate($pagination, ['*'], 'page', $page);
        // ->appends(['search' => $request->search, 'year' => $request->year]);
    }

    public function show(mixed $id): mixed
    {
        return $this->model
            ->with(['store' => function ($query) {
                $query->select('id', 'name');
            }, 'users'])
            ->find($id);
    }

    public function getTransactionsByOutlet(string $id, int $perPage = 5, int $page = 1)
    {
        $outlet = $this->model->with('store')->find($id);
        if (!$outlet || !$outlet->store) {
            return null;
        }

        return $outlet->store->transactions()
            ->with('transaction_details:id,transaction_id,quantity')
            ->select('id', 'transaction_code', 'total_price', 'transaction_status', 'created_at')
            ->latest()
            ->paginate($perPage, ['*'], 'transaction_page', $page);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->with('store', 'users')->where('is_delete', 0)->find($id);
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
