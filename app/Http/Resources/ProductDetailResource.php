<?php

namespace App\Http\Resources;

use App\Contracts\Repositories\Master\ProductStockRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray($request)
    {
        $user = auth()->user();

        return [
            'product' => $this->product?->name,
            'id' => $this->id,
            'category' => $this->category?->name,
            'category_id' => $this->category?->id,
            'price' => $this->price,
            'variant_name' => $this->variant_name,
            'product_code' => $this->product_code,
            'product_image' => $this->image,
            'transaction_details_count' => $this->transaction_details_count ?? 0,
            'stock' => $user->hasRole('outlet')
                ? optional($this->productStockOutlet)->stock ?? 0
                : optional($this->productStockWarehouse)->stock ?? 0,
        ];
    }
}
