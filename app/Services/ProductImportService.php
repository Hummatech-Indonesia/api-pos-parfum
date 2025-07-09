<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Collection;

class ProductImportService
{
    public function mapProduct(array $firstRow): array
    {

        $user = auth()->user();

        $data = [
            'name' => $firstRow['name'],
            'store_id' => $user->store_id,
            'category_id' => $firstRow['category_id']
        ];

        if ($user->hasRole('warehouse')) {
            $data['warehouse_id'] = $user->warehouse_id;
        } elseif ($user->hasRole('outlet')) {
            $data['outlet_id'] = $user->outlet_id;
        }

        return $data;
    }

    public function mapProductDetail(array $row, string $productId): array
    {
        return [
            'product_id' => $productId,
            'material' => $row['material'] ?? null,
            'unit' => $row['unit'] ?? null,
            'capacity' => $row['capacity'] ?? 0,
            'weight' => $row['weight'] ?? 0,
            'density' => $row['density'] ?? 0,
            'price' => $row['price'] ?? 0,
            'unit_id' => $row['unit'],
            'price_discount' => $row['price_discount'] ?? null,
            'variant_name' => $row['variant_name'] ?? null,
            'product_code' => $row['product_code'] ?? null,
        ];
    }

    public function mapStock(array $row, String $productId, String $detailId): array
    {
        return [
            'warehouse_id' => auth()->user()->warehouse_id ?? null,
            'outlet_id' => auth()->user()->outlet_id ?? null,
            'product_id' => $productId,
            'product_detail_id' => $detailId,
            'stock' => $row['stock'] ?? 0,
        ];
    }
}
