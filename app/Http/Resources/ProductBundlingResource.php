<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductBundlingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kode_Bundling' => $this->bundling_code ?? '-',
            'harga' => $this->price ?? 0,
            'stock' => $this->stock ?? 0,
            'status' => ($this->stock ?? 0) > 0 ? 'active' : 'non-active',
            'category' => $this->category->name ?? '-',
            'bundling_material_count' => $this->details->count(),
            'bundling_material' => $this->details->map(function ($detail) {
                return [
                    'product_detail_id' => $detail->product_detail_id,
                    'image' => $this->product->image ?? null,
                ];
            }),
        ];
    }
}
