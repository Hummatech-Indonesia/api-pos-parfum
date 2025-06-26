<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutletDetailWithTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_price'        => $this->total_price,
            'transaction_status' => $this->transaction_status,
            'created_at'         => $this->created_at,
        ];
    }
}
