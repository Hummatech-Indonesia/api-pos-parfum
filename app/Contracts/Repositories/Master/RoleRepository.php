<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\RoleInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\Role;
use Illuminate\Foundation\Mix;

class RoleRepository extends BaseRepository implements RoleInterface
{
    public function __construct(Role $role)
    {
        $this->model = $role;
    }

    public function get(?int $perPage = null): mixed
    {
        return $perPage ? $this->model->paginate($perPage) : $this->model->get();
    }


    public function show(mixed $id): mixed
    {
        return $this->model
            ->withTrashed()
            ->with([
                'users' => function ($query) {
                    $query->with(['tokens' => function ($q) {
                        $q->latest('created_at')->limit(1); // ambil token terakhir
                    }]);
                }
            ])
            ->withCount('users')
            ->find($id);
    }

    
    public function store(array $data): mixed
    {
        return $this->model->create($data);
    }

    // public function show(mixed $id): mixed
    // {
    //     return $this->model->with('store')->withCount('roles')->find($id);
    // }

    public function update(mixed $id, array $data): mixed
    {
        return $this->show($id)->update($data);
    }

    public function delete(mixed $id): mixed
    {
        $role = $this->show($id);
        return $role ? $role->delete() : false; 
    }

    public function restore(mixed $id): mixed
    {
        $role = $this->model->withTrashed()->find($id);
        return $role ? $role->restore() : false;
    }


}