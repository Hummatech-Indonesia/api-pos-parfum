<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => $this->user?->name,
            'user_image' => $this->user?->image,
            'time' => $this->time,
            'date' => $this->date,
            'start_price' => $this->start_price,
            'end_price' => $this->end_price,
        ];
    }
}
