<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\OutletResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PengeluaranResource extends JsonResource
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
            'nama_pengeluaran' => $this->nama_pengeluaran,
            'nominal_pengeluaran' => $this->nominal_pengeluaran,
            'deskripsi' => $this->deskripsi,
            'image' => $this->image,
            'tanggal_pengeluaran' => $this->tanggal_pengeluaran,
            'kategori_pengeluaran' => [
                'id' => $this->kategori_pengeluaran?->id,
                'nama' => $this->kategori_pengeluaran?->nama,
                'outlet_id' => $this->kategori_pengeluaran?->outlet_id,
                'warehouse_id' => $this->kategori_pengeluaran?->warehouse_id
            ],
            'outlet' => new OutletResource($this->whenLoaded('outlet')),
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
