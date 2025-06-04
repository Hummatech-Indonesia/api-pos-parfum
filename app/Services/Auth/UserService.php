<?php

namespace App\Services\Auth;

use App\Traits\UploadTrait;
use Spatie\Permission\Models\Role;

class UserService 
{
    use UploadTrait;

    public function mappingDataUser(array $data): array
    {
        $data = (object)$data;

        $image = null;
        try{
            if(isset($data->image)) {
                $image = $this->upload("users", $data->image);
            }
        }catch(\Throwable $th){ }

        $result = [
            "name" => $data->name,
            "email" => $data->email,
            "password" => bcrypt($data->password)
        ];

        if($image) {
            $result["image"] = $image;
        }

        return $result;
    }

    public function addStore(array $data): array
    {
        $data = (object)$data;

        $image = null;
        try{
            if(isset($data->logo)) {
                $image = $this->upload("stores", $data->logo);
            }
        }catch(\Throwable $th){ }


        return [
            "user_id" => $data->user_id,
            "name" => $data->name_store,
            "address" => $data->address_store,
            "logo" => $image 
        ];
    }

    public function mapRole () {
        return Role::all();
    }
}