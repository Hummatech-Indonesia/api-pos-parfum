<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'outlet_id' => $this->outlet_id,
            'warehouse_id' => $this->warehouse_id,
            'status' => $this->status,
            'variant_chose' => $this->detailRequestStock->count(),
            'requested_stock_count' => $this->detailRequestStock->sum('requested_stock'),
            'requested_at' => $this->created_at,
            'requested_stock' => $this->detailRequestStock->map(function ($detail) {
                return [
                    'product_name' => optional($detail->detailProduct->product)->name,
                    'variant_name' => optional($detail->detailProduct)->variant_name,
                    'requested_stock' => $detail->requested_stock,
                    'unit_id' => $detail->unitRelation?->id,
                    'unit_code' => $detail->unitRelation?->code
                ];
            }),
            'warehouse' => [
                'id' => optional($this->warehouse)->id,
                'name' => optional($this->warehouse)->name,
                'image' => optional($this->warehouse)->image,
            ],
        ];
    }
}
