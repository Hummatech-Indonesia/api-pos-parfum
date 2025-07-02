<?php

namespace App\Http\Resources;

use App\Contracts\Repositories\Master\ProductStockRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray($request)
    {
        $user = auth()->user();

        $data =  [
            'product' => $this->product?->name,
            'id' => $this->id,
            'category' => $this->category?->name,
            'price' => $this->price,
            'variant_name' => $this->variant_name,
            'product_code' => $this->product_code,
            'product_image' => $this->image,
            'transaction_details_count' => $this->transaction_details_count ?? 0,
        ];
        if ($user->hasRole('outlet')) {
            $data['stock'] = optional($this->productStockOutlet)->stock;
        } elseif ($user->hasRole('warehouse')) {
            $data['stock'] = optional($this->productStockWarehouse)->stock;
        }

        return $data;
    }
}
