<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\OutletResource;
use Illuminate\Http\Resources\Json\JsonResource;

class KategoriPengeluaranResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'outlet' => new OutletResource($this->whenLoaded('outlet')),
            'warehouse' => [
                'id' => $this->warehouse_id,
                'store_id' => $this->warehouse->store_id,
                'name' => $this->warehouse->name,
                'adress' => $this->warehouse->adress,
                'telp' => $this->warehouse->telp,
                'person_responsible' => $this->warehouse->person_responsible,
                'image' => $this->warehouse->image,
                'is_delete' => $this->warehouse->is_delete,
            ],
        ];
    }
}
