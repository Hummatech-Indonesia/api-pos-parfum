<?php

namespace App\Services\Auth;

use App\Traits\UploadTrait;
use Spatie\Permission\Models\Role;

class UserService
{
    use UploadTrait;

    public function mappingDataUser(array $data): array
    {
        if(!isset($data["phone"])) $data["phone"] = null;
        $data = (object)$data;

        $image = null;
        try {
            if (isset($data->image)) {
                $image = $this->upload("users", $data->image);
            }
        } catch (\Throwable $th) {
        }

        $result = [
            "name" => $data->name,
            "email" => $data->email,
            "phone" => $data->phone,
            "password" => bcrypt($data->password)
        ];

        if ($image) {
            $result["image"] = $image;
        }

        return $result;
    }

    public function addStore(array $data): array
    {
        $data = (object)$data;

        $image = null;
        try {
            if (isset($data->logo)) {
                $image = $this->upload("stores", $data->logo);
            }
        } catch (\Throwable $th) {
        }


        return [
            "user_id" => $data->user_id,
            "name" => $data->name_store,
            "address" => $data->address_store,
            "logo" => $image
        ];
    }

    public function mapRole()
    {
        return Role::all();
    }
    public function prepareUserCreationData(array $data, array $requestedRoles): array
    {
        $user = auth()->user();
        $userRole = $user->getRoleNames()->first();

        $allowedRoles = match ($userRole) {
            'owner' => ['owner', 'outlet', 'employee', 'warehouse', 'auditor', 'manager', 'cashier', 'admin'],
            'outlet' => ['outlet', 'employee', 'cashier'],
            'warehouse' => ['warehouse', 'employee', 'cashier'],
            'cashier' => ['member'],
            default => [],
        };

        $invalidRoles = array_diff($requestedRoles, $allowedRoles);

        if (count($invalidRoles) > 0) {
            $invalidList = implode(', ', $invalidRoles);
            throw new \Exception("Role '{$invalidList}' tidak diizinkan untuk dibuat oleh '{$userRole}'");
        }

        return [
            ...$this->mappingDataUser($data),
            'store_id' => $user->store->id ?? $user->store_id,
            'outlet_id' => $user->hasRole('outlet') ? $user->outlet_id : null,
            'warehouse_id' => $user->hasRole('warehouse') ? $user->warehouse_id : null,
            'is_delete' => 0,
        ];
    }
}
