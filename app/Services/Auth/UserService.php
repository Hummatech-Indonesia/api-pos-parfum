<?php

namespace App\Services\Auth;

use App\Traits\UploadTrait;

class UserService 
{
    use UploadTrait;

    public function mappingDataUser(array $data): array
    {
        $data = (object)$data;

        return [
            "name" => $data->name,
            "email" => $data->email,
            "password" => bcrypt($data->password)
        ];
    }

    public function addStore(array $data): array
    {
        $data = (object)$data;

        $logo = null;
        try{
            if(isset($data->logo)) {
                $logo = $this->upload("store", $data->logo);
            }
        }catch(\Throwable $th){ }
        $data->logo = $logo;


        return [
            "user_id" => $data->user_id,
            "name" => $data->name_store,
            "address" => $data->address_store,
            "logo" => $data->logo 
        ];
    }
}