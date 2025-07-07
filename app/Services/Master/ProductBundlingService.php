<?php

namespace App\Services\Master;

use App\Traits\UploadTrait;

class ProductBundlingService
{
    use UploadTrait;

    public function mapProductData(array $data): array
    {
        $image = null;

        try {
            if(isset($data["image"])) {
                $image = $this->upload("products", $data["image"]);
            }
        } catch (\Throwable $th) {

        }
        return [
            'id' => uuid_create(),
            'store_id' => $data['store_id'] ?? auth()->user()->store_id,
            'name' => $data['name'],
            'unit_type' => 'unit',
            'image' => $image ?? 'default/Default.jpeg',
            // 'qr_code' => $data['kode_Blend'] ?? null,
            'description' => $data['deskripsi'] ?? null,
            'is_delete' => 0,
            'category_id' => $data['category_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'outlet_id' => $data['outlet_id'] ?? null,
        ];
    }


    public function mapBundlingData(array $data, string $productId, int $categoryId): array
    {
        $name = $data['name'];
        $generatedCode = $this->generateBundlingCode($name);

        return [
            'id' => $data['id'] ?? uuid_create(),
            'product_id' => $productId,
            'name' => $name,
            'category_id' => $categoryId,
            'stock' => null,
            'price' => $data['harga'] ?? 0,
            'bundling_code' => $data['kode_Blend'] ?? $generatedCode,
            'user_id' => auth()->user()?->id,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'outlet_id' => $data['outlet_id'] ?? null,
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

    public function generateBundlingCode(string $name): string
    {
        $words = explode(' ', strtoupper($name));
        $initials = '';

        foreach ($words as $word) {
            $initials .= substr($word, 0, 1);
        }

        return 'BNDL-' . $initials;
    }

}
