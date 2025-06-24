<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'stock' => $this->stock,
            'price' => $this->price,
            'variant_name' => null,
            'product_code' => $this->product_code,
            'product_image' => $this->image,
            'transaction_details_count' => $this->transaction_details_count ?? 0,
            'category' => [
                'name' => optional($this->category)->name,
            ]
        ];
    }
}
