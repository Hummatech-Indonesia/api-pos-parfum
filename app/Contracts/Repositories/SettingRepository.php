<?php

namespace App\Contracts\Repositories;

use App\Contracts\Interfaces\SettingInterface;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class SettingRepository extends BaseRepository implements SettingInterface
{
    public function __construct(Setting $setting)
    {
        $this->model = $setting;
    }

    public function get(): mixed
    {
        return $this->model->all();
    }

    public function store(array $data): mixed
    {
        return $this->model->create($data);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->query()->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->model->find($id)->update($data);
    }

    public function delete(mixed $id): mixed
    {
        return $this->show($id)->delete();
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->with('store')
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%');
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

    public function customQuery(array $data): mixed
    {
        return $this->model->query()
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }

    public function allDataTrashed(): mixed // Untuk mencari data yang dihapus
    {
        return $this->model->withTrashed()->get();
    }

    public function restore(string $id)
    {
        $audit = $this->model->withTrashed()->findOrFail($id);
        $audit->restore();
        return $audit;
    }
}
