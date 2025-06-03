<?php

namespace App\Services\Master;

class ProductBundlingService
{
    public function mapProductData(array $product): array
    {
        return [
            'id' => uuid_create(),
            'store_id' => $product['store_id'],
            'name' => $product['name'],
            'unit_type' => $product['unit_type'],
            'image' => $product['image'] ?? null,
            'qr_code' => $product['qr_code'] ?? null,
            'is_delete' => 0,
            'category_id' => $product['category_id'] ?? null,
        ];
    }

    public function mapBundlingData(array $validated): array
    {
        return [
            'id' => $validated['id'] ?? uuid_create(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ];
    }

    public function mapDetailData(array $details): array
    {
        return $details;
    }
}
