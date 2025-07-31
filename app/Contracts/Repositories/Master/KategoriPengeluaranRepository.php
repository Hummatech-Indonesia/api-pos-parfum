<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\KategoriPengeluaranInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\KategoriPengeluaran;

class KategoriPengeluaranRepository extends BaseRepository implements KategoriPengeluaranInterface
{

    public function __construct(KategoriPengeluaran $kategoriPengeluaran)
    {
        $this->model = $kategoriPengeluaran;
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
            ->with('outlet', 'warehouse')
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }

    public function customPaginate(int $pagination = 8, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->with('outlet', 'warehouse')
            ->when($data, function ($query) use ($data) {
                if (!empty($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('nama', 'like', '%' . $data["search"] . '%');
                    });
                }

                if (!empty($data["start_date"])) {
                    $query->whereDate('created_at', '>=', $data["start_date"]);
                }

                if (!empty($data["end_date"])) {
                    $query->whereDate('created_at', '<=', $data["end_date"]);
                }
            })
            ->paginate($pagination, ['*'], 'page', $page);
    }

    public function show(mixed $id): mixed
    {
        return $this->model
            ->with('outlet', 'warehouse')
            ->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        $model = $this->model->select('id')->findOrFail($id);

        if (!$model) {
            return null;
        }

        $model->update($data);

        return $model->fresh();
    }

    public function delete(mixed $id): mixed
    {
        $model = $this->model->select('id')->findOrFail($id);

        if (!$model) {
            return null;
        }

        $model->delete();

        return $model->fresh();
    }
}
