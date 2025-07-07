<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockRequestDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_name' => optional($this->detailProduct?->product)->name,
            'variant_name' => optional($this->detailProduct)->variant_name,
            'requested_stock' => $this->requested_stock,
            'kategori' => optional($this->detailProduct->product?->category)->name,
            'variant_code' => optional($this->detailProduct)->product_code,
            'unit_id' => $this->unitRelation?->id,
            'unit_code' => $this->unitRelation?->code
        ];
    }
}
