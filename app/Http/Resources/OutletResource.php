<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutletResource extends JsonResource
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
            'address' => $this->address,
            'telp' => $this->telp,
            'image' => $this->image,
            'transaction_count' => $this->store?->transactions()?->count() ?? 0,
            'pemilik_outlet' => $this->users?->first()?->name,
        ];
    }
}
