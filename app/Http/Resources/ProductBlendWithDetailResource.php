<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBlendWithDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nama_blending' => $this->product->nama_blending ?? $this->product->name ?? null,
            'Quantity' => $this->result_stock,
            'description' => $this->description,
            'tanggal_pembuatan' => $this->date,
            'jumlah_bhn_baku' => $this->jumlah_bhn_baku,

            'details' => [
                'data' => ProductBlendDetailResource::collection($this->productBlendDetails->items()),
                'current_page' => $this->productBlendDetails->currentPage(),
                'last_page' => $this->productBlendDetails->lastPage(),
                'per_page' => $this->productBlendDetails->perPage(),
                'total' => $this->productBlendDetails->total(),
            ]
        ];
    }
}
