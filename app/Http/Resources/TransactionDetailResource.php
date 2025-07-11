<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class TransactionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        Log::info("Data Product", [$this->product]);
        return [
            'product_name' => optional($this->product)?->name ?? null,
            'variant_name' => $this->variant_name ?? null,
            'price' => (float) $this->price,
            'quantity' => $this->quantity . ' ' . $this->unit,
            'discount' => optional($this->product)?->price_discount ?? 0,
            'total_price' => $this->price * $this->quantity,
        ];
    }
}
