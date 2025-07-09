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
            'store_name' => $this->store_name,
            'total_price' => $this->total_price,
            'store_location' => $this->store_location,
            'status' => $this->status,
            'variant_chose' => $this->detailRequestStock->count(),
            'requested_stock_count' => $this->detailRequestStock->sum('requested_stock'),
            'requested_at' => $this->created_at,
            'note' => $this->note,
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
                'address' => optional($this->warehouse)->address,

                'products' => $this->detailRequestStock->map(function ($detail) {
                    $product = optional($detail->detailProduct->product);
                    $variant = optional($detail->detailProduct);

                    $requestedStock = $detail->requested_stock;
                    $unitCode = optional($detail->unitRelation)->code;
                    $price = $detail->price ?? 0;

                    $stock = optional($variant->productStockWarehouse)->stock ?? 0;

                    return [
                        'product_name' => $product->name,
                        'product_description' => $product->description,
                        'variant_name' => $variant->variant_name,
                        'variant_code' => $variant->product_code,
                        'unit_code' => $unitCode,
                        'requested_stock' => $requestedStock,
                        'available_stock' => $stock,
                        'total_price' => $requestedStock * $price,
                    ];
                })->values(),
            ],
        ];
    }
}
