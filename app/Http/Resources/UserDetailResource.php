<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name'        => $this->name,
            'email'       => $this->email,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            'image'       => $this->image,
            'phone'       => $this->phone,
            'roles' => $this->roles->pluck('name'),

            'store' => $this->store ? [
                'name'    => $this->store->name,
                'address' => $this->store->address,
            ] : null,

            'related_store' => $this->related_store ? [
                'name'    => $this->related_store->name,
                'address' => $this->related_store->address,
            ] : null,

            'warehouse' => $this->warehouse ? [
                'name'    => $this->warehouse->name,
                'address' => $this->warehouse->address,
            ] : null,
        ];
    }
}
