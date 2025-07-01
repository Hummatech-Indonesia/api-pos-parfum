<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductBundlingDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id ?? null,
            'name' => $this->name,
            'kode_Bundling' => $this->bundling_code ?? '-',
            'harga' => $this->price ?? 0,
            'stock' => $this->stock ?? 0,
            'status' => ($this->stock ?? 0) > 0 ? 'active' : 'non-active',
            'category' => $this->category->name ?? '-',
            'description' => $this->product->description ?? null,

            'bundling_material_count' => $this->whenLoaded('details', fn() => $this->details->count()),

            'bundling_material' => $this->whenLoaded('details', function () {
                return $this->details->map(function ($detail) {
                    return [
                        'product_detail_id' => $detail->product_detail_id,
                        'variant_name' => $detail->productDetail->variant_name ?? '-', 
                        'image' => $this->product->image ?? null,
                    ];
                });
            }),
        ];
    }
}
