<?php

namespace App\Services\Master;

use App\Models\Product;
use App\Models\ProductBundling;
use App\Models\ProductBundlingDetail;
use App\Models\ProductDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductBundlingService
{
    public function storeBundling(array $data): ProductBundling
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'id' => uuid_create(),
                "store_id" => $data['product']['store_id'],
                'name' => $data['product']['name'],
                'unit_type' => $data['product']['unit_type'],
                'image' => $data['product']['image'] ?? null,
                'qr_code' => $data['product']['qr_code'] ?? null,
                'is_delete' => 0,
                'category_id' => $data['product']['category_id'] ?? null,
            ]);
            
            $bundling = ProductBundling::create([
                'id' => $data['id'] ?? uuid_create(),
                'product_id' => $product->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category_id' => $product->category_id,
            ]);

            foreach ($data['details'] as $detail) {
                $productDetail = ProductDetail::create([
                    'id' => uuid_create(),
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'product_varian_id' => $detail['product_detail']['product_varian_id'] ?? null,
                    'material' => $detail['product_detail']['material'],
                    'unit' => $detail['product_detail']['unit'],
                    'capacity' => $detail['product_detail']['capacity'],
                    'weight' => $detail['product_detail']['weight'],
                    'density' => $detail['product_detail']['density'],
                    'price' => $detail['product_detail']['price'],
                    'price_discount' => $detail['product_detail']['price_discount'] ?? null,
                ]);

                ProductBundlingDetail::create([
                    'product_bundling_id' => $bundling->id,
                    'product_detail_id' => $productDetail->id,
                    'unit' => $detail['unit'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                ]);
            }

            return $bundling->load('details');
        });
    }

    public function updateBundling(ProductBundling $bundling, array $data): ProductBundling
    {
        return DB::transaction(function () use ($bundling, $data) {
            
            $bundling->details()->delete();

            foreach ($data['details'] as $detail) {
                ProductBundlingDetail::create([
                    'product_bundling_id' => $bundling->id,
                    'product_detail_id' => $detail['product_detail_id'],
                    'unit' => $detail['unit'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                ]);
            }

            return $bundling->load('details');
        });
    }

    public function deleteBundling(ProductBundling $bundling): void
    {
        DB::transaction(function () use ($bundling) {
            $bundling->details()->delete();
            $bundling->delete();
        });
    }
}
