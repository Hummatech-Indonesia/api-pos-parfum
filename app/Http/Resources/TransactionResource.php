<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'cashier_name' => $this->user?->name,
            'buyer_name' => $this->user_id ? $this->user?->name : $this->user_name,
            'quantity' => $this->quantity,
            'amount_price' => $this->amount_price,
            'payment_time' => $this->payment_time,

        ];
    }
}
