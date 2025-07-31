<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\OutletResource;
use Carbon\Carbon;
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
            'created_at' => $this->created_at,
            'warehouse' => [
                'id' => $this->warehouse_id,
                'store_id' => $this->warehouse->store_id ?? null,
                'name' => $this->warehouse->name ?? null,
                'adress' => $this->warehouse->adress ?? null,
                'telp' => $this->warehouse->telp ?? null,
                'person_responsible' => $this->warehouse->person_responsible ?? null,
                'image' => $this->warehouse->image ?? null,
                'is_delete' => $this->warehouse->is_delete ?? null,
            ],
        ];
    }
}
