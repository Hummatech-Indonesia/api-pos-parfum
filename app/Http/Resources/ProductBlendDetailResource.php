<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBlendDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_detail_id' => $this->product_detail_id,
            'product_name' => $this->productDetail?->productAll?->name ?? null,
            'variant_name' => $this->productDetail?->variant_name ?? null,
            'unit_id' => $this->unit_id,
            'unit_code' => $this->unit?->code ?? null,
            'quantity' => $this->used_stock,
            'stock' => $this->productStock?->stock ?? 0,
        ];
    }
}
