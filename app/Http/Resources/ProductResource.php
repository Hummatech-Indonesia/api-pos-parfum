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
            'category' => [
                'name' => optional($this->category)->name,
            ],
            'details' => ProductDetailResource::collection($this->whenLoaded('details'))
        ];
    }
}
