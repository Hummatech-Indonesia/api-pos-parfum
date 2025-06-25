<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBlendResource extends JsonResource
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
            'blend_name' => $this->product->blend_name ?? $this->product->name ?? null,
            'quantity' => $this->result_stock,
            'description' => $this->description,
            'date' => $this->date,
            'created_at' => $this->created_at->toDateTimeString(),
            'used_product_count' => $this->used_product_count,
            'used_products' => $this->productBlendDetails->map(function ($detail) {
                return [
                    'variant_name' => $detail->productDetail->variant_name ?? null,
                    'used_stock' => $detail->used_stock,
                    'product_name' => $detail->productDetail->product->name ?? null,
                ];
            }),
        ];
    }
}
