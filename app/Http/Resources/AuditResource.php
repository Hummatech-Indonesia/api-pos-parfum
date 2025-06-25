<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->auditDetails;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'date' => $this->date,
            'status' => $this->status,
            'auditor' => $this->user->name,
            'variant_count' => $this->auditDetails->count(),
            'store_name' => $this->store->name,
            'retail_name' => $this->outlet->name,
            // 'items_with_discrepancy' => $items->filter(fn($i) => $i->audit_stock != $i->old_stock)->count(),
            // 'total_shortage' => $items->sum(fn($i) => max(0, $i->old_stock - $i->audit_stock)),
            'audit_detail' => $this->whenLoaded('auditDetails', function () {
                return $this->auditDetails->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'audit_stock' => $detail->audit_stock,
                        'real_stock' => $detail->old_stock,
                        'product' => $detail->details->product->name ?? null,
                        'variant_name' => $detail->details->variant_name ?? null,
                        'product_code' => $detail->details->product_code ?? null,
                    ];
                });
            }),
        ];
    }
}
