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
public function mapAuditDetails(array $products, Audit $audit): array
{
    $mappedDetails = [];

    foreach ($products as $product) {
        // Ambil stok lama jika tersedia
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
}
