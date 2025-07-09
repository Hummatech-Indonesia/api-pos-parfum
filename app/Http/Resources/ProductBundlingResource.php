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
            'created_by' => $this->getCreatedBy(),
            'bundling_material_count' => $this->details->count(),
            'bundling_material' => $this->details->map(function ($detail) {
                $user = auth()->user();
                return [
                    'product_detail_id' => $detail->product_detail_id,
                    'quantity' => $detail->quantity ?? null,
                    'sum_stock' => $user->hasRole('outlet')
                    ? optional($detail->productDetail->productStockOutlet) ?? 0 
                    : optional($detail->productDetail->productStockWarehouse) ?? 0,
                    'image' => $this->product->image ?? null,
                    'unit_id' => $detail->unitRelation?->id,
                    'unit_code' => $detail->unitRelation->code ?? null
                ];
            }),
        ];
    }

    private function getCreatedBy(): ?string
    {
        if (!$this->user) return null;

        if ($this->user->hasRole('outlet')) return 'retail';
        if ($this->user->hasRole('warehouse')) return 'warehouse';

        return 'Unknown';
    }

}
