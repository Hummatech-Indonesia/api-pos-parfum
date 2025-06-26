<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutletDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'address'           => $this->address,
            'telp'              => $this->telp,
            'created_at'        => $this->created_at,
            'image'             => $this->image,
            'transaction_count' => $this->store?->transactions()?->count() ?? 0,
            'worker_count'      => $this->users?->count() ?? 0,
            'owner'             => $this->users?->first()?->name ?? null,
            'status'            => $this->is_delete == 0 ? 'Active' : 'Unactive',
        ];
    }
}
