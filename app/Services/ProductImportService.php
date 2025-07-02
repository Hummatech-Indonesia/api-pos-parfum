<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ProductImportService
{
    public function mapProduct(array $firstRow, int $categoryId): array
    {
        return [
            'name' => $firstRow['name'],
            'category_id' => $categoryId,
            'store_id' => auth()->user()->store_id,
        ];
    }

    public function mapProductDetail(array $row, string $productId, int $categoryId): array
    {
        return [
            'product_id' => $productId,
            'category_id' => $categoryId,
            'material' => $row['material'] ?? null,
            'unit' => $row['unit'] ?? null,
            'capacity' => $row['capacity'] ?? 0,
            'weight' => $row['weight'] ?? 0,
            'density' => $row['density'] ?? 0,
            'price' => $row['price'] ?? 0,
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
