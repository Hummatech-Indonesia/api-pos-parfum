<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountVoucherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->desc,
            'minimum_purchase' => $this->min,
            'percentage' => $this->percentage,
            'nominal' => $this->nominal,
            'is_member' => $this->is_member,
            'type' => $this->type,
            'active' => $this->active,
            'start_date' => $this->start_date,
            'end_date' => $this->expired,
            'created_at' => $this->created_at,

            'product_detail' => $this->whenLoaded('details', function () {
                return [
                    'id' => $this->details->id,
                    'variant_name' => $this->details->variant_name,
                    'product_code' => $this->details->product_code,
                    'product_image' => $this->details->product->image,
                ];
            }),
        ];
    }
}
