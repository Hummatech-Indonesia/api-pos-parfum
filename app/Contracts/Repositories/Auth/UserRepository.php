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
        try{
            $role = $data["role"];
            unset($data["role"]);
        }catch(\Throwable $th){ }

        return $this->model->query()
        ->with('store','related_store','roles','warehouse','outlet')
        ->when(count($data) > 0, function ($query) use ($data){
            if(isset($data["user_id"])){
                $query->whereIn('id',$data["user_id"]);
                unset($data["user_id"]);
            }

            if(isset($data["warehouse"])){
                if($data["warehouse"] == "false"){
                    $query->whereNotHas("warehouse");
                }else if ($data["warehouse"] == "true"){
                    $query->whereHas('warehouse');
                }
                unset($data["warehouse"]);
            }

            if(isset($data["outlet"])){
                if($data["outlet"] == "false"){
                    $query->whereNotHas("outlet");
                }else if ($data["outlet"] == "true"){
                    $query->whereHas('outlet');
                }
                unset($data["outlet"]);
            }

            foreach ($data as $index => $value){
                $query->where($index, $value);
            }
        })
        ->when($role, function ($query) use ($role){
            $query->role($role);
        });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        $role = null;
        try{
            $role = $data["role"];
            unset($data["role"]);
        }catch(\Throwable $th){ }

        return $this->model->query()
        ->with('store','related_store','roles','warehouse','outlet')
        ->when(count($data) > 0, function ($query) use ($data){
            if(isset($data["search"])){
                $query->where(function ($query2) use ($data) {
                    $query2->where('name', 'like', '%' . $data["search"] . '%')
                    ->orwhere('email', 'like', '%' . $data["search"] . '%');
                });
                unset($data["search"]);
            }
            
            foreach ($data as $index => $value){
                $query->where($index, $value);
            }
        })
        ->when($role, function ($query) use ($role){
            $query->role($role);
        })
        ->paginate($pagination, ['*'], 'page', $page)
        ->appends(['search' => isset($data["search"]) ?? '']);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->with('store','related_store','roles')->find($id);
    }
    
    public function checkUserActive(mixed $id): mixed
    {
        return $this->model->with('store','related_store','roles')->where('is_delete',0)->find($id);
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