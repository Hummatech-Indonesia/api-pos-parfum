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
}
