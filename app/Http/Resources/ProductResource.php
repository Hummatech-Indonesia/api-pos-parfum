<?php

namespace App\Http\Resources;

use App\Contracts\Repositories\Master\ProductStockRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductDetailResource;
use App\Models\ProductStock;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        $isBundling = $this->relationLoaded('productBundling')
            ? $this->productBundling !== null
            : $this->productBundling()->exists();
        return [
            'id' => $isBundling
                ? $this->productBundling?->id
                : $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'details_sum_stock' => $this->details_sum_stock,
            'category' => $this->category?->name,
            'created_by' => $this->getCreatedBy(),
            'description' => $this->description,
            'is_bundling' => $isBundling,
            'bundling_price' => $this->when(
                $isBundling,
                fn() => $this->productBundling?->price
            ),
            'bundling_detail' => $this->when(
                $this->relationLoaded('productBundling') && $this->productBundling,
                function () {
                    $user = auth()->user();

                    return $this->productBundling->details->map(function ($item) use ($user) {
                        $productDetail = $item->productDetail;

                        if (!$productDetail) return null;

                        return [
                            'product_name' => $productDetail->product?->name,
                            'category_id' => $productDetail->category?->id,
                            'category' => $productDetail->category?->name,
                            'product_detail_id' => $productDetail->id,
                            'product_code' => $productDetail->product_code,
                            'variant_name' => $productDetail->variant_name,
                            'sum_stock' =>  $user->hasRole('outlet')
                                ? optional($productDetail->productStockOutlet)->stock ?? 0
                                : optional($productDetail->productStockWarehouse)->stock ?? 0,
                            'quantity' => $item->quantity,
                        ];
                    })->filter();
                }
            ),

            'product_detail' => $this->when(
                !$this->relationLoaded('productBundling') || is_null($this->productBundling),
                function () {
                    return $this->details->map(function ($detail) {
                        $user = auth()->user();
                        return [
                            'id' => $detail->id,
                            'category' => $detail->category?->name,
                            'category_id' => $detail->category?->id,
                            'unit_id' => $detail->unit_id,
                            'unit_code' => $detail->unitRelation?->code ?? null,
                            'price' => $detail->price,
                            'variant_name' => $detail->variant_name,
                            'product_code' => $detail->product_code,
                            'product_image' => $detail->image,
                            'transaction_details_count' => $detail->transaction_details_count ?? 0,
                            'stock' => $user->hasRole('outlet')
                                ? optional($detail->productStockOutlet)->stock ?? 0
                                : optional($detail->productStockWarehouse)->stock ?? 0,
                        ];
                    });
                }
            ),


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
