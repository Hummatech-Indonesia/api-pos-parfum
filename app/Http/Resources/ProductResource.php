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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'details_sum_stock' => $this->details_sum_stock,
            'category' => $this->category?->name,
            'description' => $this->description,
            'product_detail' => $this->whenLoaded('details', function () {

                return $this->details->map(function ($detail) {
                    $user = auth()->user();
                    $data = [
                        'id' => $detail->id,
                        'category' => $detail->category?->name,
                        'price' => $detail->price,
                        'variant_name' => $detail->variant_name,
                        'product_code' => $detail->product_code,
                        'product_image' => $detail->image,
                    ];
                    if ($user->hasRole('outlet')) {
                        $data['stock'] = optional($detail->productStockOutlet)->stock;
                    } elseif ($user->hasRole('warehouse')) {
                        $data['stock'] = optional($detail->productStockWarehouse)->stock;
                    }


                    return $data;
                });
            }),

        ];
    }
}
