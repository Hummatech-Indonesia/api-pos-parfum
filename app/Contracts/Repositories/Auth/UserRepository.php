<?php

namespace App\Contracts\Repositories\Auth;

use App\Contracts\Interfaces\Auth\UserInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\User;

class UserRepository extends BaseRepository implements UserInterface
{

    public function __construct(User $user){
        $this->model = $user;
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
        $role = null;
        $type_role = null;
        try{
            $role = $data["role"];
            $type_role = $data["type_role"];
        }catch(\Throwable $th){ }

        return $this->model->query()
        ->when(count($data) > 0, function ($query) use ($data){
            foreach ($data as $index => $value){
                $query->where($index, $value);
            }
        })
        ->when($role, function ($query) use ($role, $type_role){
            if(!$type_role && !is_array($role)) $query->whereRelation('role','name',$role);
            else if ($type_role == "in") $query->whereRelationIn('role','name',$role);
            else if ($type_role == "not") $query->whereRelationNotIn('role','name',$role);
        });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        $role = null;
        $type_role = null;
        try{
            $role = $data["role"];
            $type_role = $data["type_role"];
        }catch(\Throwable $th){ }

        return $this->model->query()
        ->when(count($data) > 0, function ($query) use ($data){
            foreach ($data as $index => $value){
                $query->where($index, $value);
            }
        })
        ->when($role, function ($query) use ($role, $type_role){
            if(!$type_role && !is_array($role)) $query->whereRelation('role','name',$role);
            else if ($type_role == "in") $query->whereRelationIn('role','name',$role);
            else if ($type_role == "not") $query->whereRelationNotIn('role','name',$role);
        })
        ->paginate($pagination, ['*'], 'page', $page);
        // ->appends(['search' => $request->search, 'year' => $request->year]);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->with('store')->find($id);
    }
    
    public function checkUserActive(mixed $id): mixed
    {
        return $this->model->with('store')->where('is_delete',0)->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->show($id)->update($data);
    }

    public function delete(mixed $id): mixed 
    {
        return $this->show($id)->update(['is_delete' => 1]);
    }
}