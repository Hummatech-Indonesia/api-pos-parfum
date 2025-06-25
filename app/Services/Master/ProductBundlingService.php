<?php

namespace App\Services\Master;

class ProductBundlingService
{

    public function mapProductData(array $data): array
    {
        return [
            'id' => uuid_create(),
            'store_id' => $data['store_id'] ?? auth()->user()->store_id,
            'name' => $data['name'], // Ini "product name"
            'unit_type' => 'unit', // Sesuaikan jika ada
            'image' => $data['image'] ?? null,
            // 'qr_code' => $data['kode_Blend'] ?? null,
            'description' => $data['deskripsi'] ?? null,
            'is_delete' => 0,
            'category_id' => $data['category_id'] ?? null,
        ];
    }


    public function mapBundlingData(array $data, string $productId, int $categoryId): array
    {
        return [
            'id' => $data['id'] ?? uuid_create(),
            'product_id' => $productId,
            'name' => $data['name'], // Redundant, simpan juga di bundling
            // 'description' => $data['deskripsi'] ?? null,
            'category_id' => $categoryId,
            'stock' => $data['quantity'] ?? 0,
            'price' => $data['harga'] ?? 0,
            'bundling_code' => $data['kode_Blend'] ?? null,
        ];
    }

    public function mapBundlingMaterial(array $details, int $bundlingStock): array
    {
        return collect($details[0]['product_bundling_material'])
            ->map(function ($item) use ($bundlingStock) {
                return [
                    'product_detail_id' => $item['product_detail_id'],
                    'unit' => 'pcs',
                    'unit_id' => null,
                    'quantity' => $bundlingStock,
                ];
            })->toArray();
    }


    public function mapDetailData(array $details): array
    {
        return $details;
    }
}
