<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductDetailResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'details_sum_stock' => $this->details_sum_stock,
            'category' => $this->category?->name,
            'description' => $this->description,
            'product_detail' => $this->whenLoaded('details', function () {
                return $this->details->map(function ($detail) {
                    // return [
                    //     'id' => $detail->id,
                    //     'category' => $detail->category?->name,
                    //     'stock' => auth()->user()->hasRole("warehouse") ? $detail->product_stock_warehouse : $detail->product_stock_outlet,
                    //     'price' => $detail->price,
                    //     'variant_name' => $detail->variant_name,
                    //     'product_code' => $detail->product_code,
                    //     'product_image' => $detail->image,
                    // ];
                    return $detail;
                });
            }),
        ];
    }
}
