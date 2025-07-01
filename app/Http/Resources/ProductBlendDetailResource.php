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
            'product_name' => $this->productDetail?->product?->name ?? null,
            'variant_name' => $this->productDetail?->variant_name ?? null,
            'quantity' => $this->used_stock,
            'stock' => $this->productStock?->stock ?? 0,
        ];
    }
}
