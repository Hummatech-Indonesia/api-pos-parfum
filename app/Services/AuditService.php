<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\AuditDetail;
use App\Models\Outlet;
use App\Models\ProductDetail;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuditService
{

    public function updateAuditData(array $data, Audit $audit): array
    {

        return [
            'status' => $data['status'] ?? $audit->status,
            'reason' => $data['reason'] ?? $audit->reason,
        ];
    }
    public function mapAuditDetails(?array $products, Audit $audit): array
    {
        if (empty($products)) return [];

        $mappedDetails = [];

        foreach ($products as $product) {
            $productStock = ProductStock::where('outlet_id', $audit->outlet_id)
                ->where('product_detail_id', $product['product_detail_id'])
                ->first();

            $oldStock = $productStock?->stock ?? 0;

            $mappedDetails[] = [
                'audit_id' => $audit->id,
                'product_detail_id' => $product['product_detail_id'],
                'old_stock' => $oldStock,
                'audit_stock' => $product['audit_stock'],
                'unit_id' => $product['unit_id'],
            ];
        }

        return $mappedDetails;
    }
    public function storeaudit(array $data): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'],
            'outlet_id' => $data['outlet_id'],
            'store_id' => $data['store_id'],
            'date' => $data['date'],
            'user_id' => auth()->id(),
            'status' => 'pending',
        ];
    }
    public function transformAudit(Audit $audit): array
    {
        $items = $audit->auditDetails->map(function ($detail) {
            return [
                'id' => $detail->id,
                'product_detail_id' => $detail->product_detail_id,
                'old_stock' => $detail->old_stock,
                'audit_stock' => $detail->audit_stock,
                'difference' => $detail->audit_stock - $detail->old_stock,
                'unit' => [
                    'id' => $detail->unit->id ?? null,
                    'name' => $detail->unit->name ?? null,
                ],
                'product' => [
                    'id' => $detail->productDetail->id ?? null,
                    'material' => $detail->productDetail->material ?? null,
                    'unit' => $detail->productDetail->unit ?? null,
                    'stock' => $detail->productDetail->stock ?? null,
                    'capacity' => $detail->productDetail->capacity ?? null,
                    'weight' => $detail->productDetail->weight ?? null,
                    'density' => $detail->productDetail->density ?? null,
                    'price' => $detail->productDetail->price ?? null,
                    'discount_price' => $detail->productDetail->price_discount ?? null,
                    'variant_name' => $detail->productDetail->variant_name ?? null,
                    'product_code' => $detail->productDetail->product_code ?? null,
                    'product_name' => $detail->productDetail->product->name ?? null,
                ]
            ];
        });

        return [
            'audit' => [
                'id' => $audit->id,
                'name' => $audit->name,
                'description' => $audit->description,
                'status' => $audit->status,
                'reason' => $audit->reason,
                'date' => $audit->date,
                'created_at' => $audit->created_at,
                'updated_at' => $audit->updated_at,
            ],
            'store' => [
                'id' => $audit->store->id,
                'name' => $audit->store->name,
            ],
            'outlet' => [
                'id' => $audit->outlet->id,
                'name' => $audit->outlet->name,
            ],
            'audit_items' => $items,
            'summary' => [
                'total_items' => $items->count(),
                'items_with_discrepancy' => $items->where('difference', '!=', 0)->count(),
                'total_shortage' => $items->sum(fn($i) => max(0, -$i['difference']))
            ]
        ];
    }
}
