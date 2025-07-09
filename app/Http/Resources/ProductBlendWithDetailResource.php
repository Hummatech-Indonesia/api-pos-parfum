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
            'id' => $this->id,
            'product_detail_id' => $this->product_detail_id,
            'unit_id' => $this->unit_id,
            'unit_code' => $this->unit?->code ?? null,
            'product_image' => $this->productDetail?->product_image ?? null,
            'product_name' => $this->productDetail?->productAll?->name ?? null,
            'variant_blending' => $this->productDetail?->variant_name ?? null,
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
