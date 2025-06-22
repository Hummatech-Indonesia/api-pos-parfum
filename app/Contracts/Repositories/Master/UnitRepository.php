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
        return $this->model->query()
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%')
                            ->orwhere('code', 'like', '%' . $data["search"] . '%');
                    });
                    unset($data["search"]);
                }

                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }

    public function customPaginate(int $pagination = 8, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%')
                            ->orwhere('code', 'like', '%' . $data["search"] . '%');
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
