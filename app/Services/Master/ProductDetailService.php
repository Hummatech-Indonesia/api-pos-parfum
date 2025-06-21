<?php

namespace App\Services\Master;

use Error;
use App\Models\Product;
use App\Traits\UploadTrait;
use App\Models\ProductDetail;
use Illuminate\Support\Facades\Log;

class ProductDetailService
{

    use UploadTrait;

    public function __construct() {}

    public function dataProductDetail(array $data)
    {
        try {
            $image = null;
            try {
                if (isset($data["product_image"])) {
                    $image = $this->upload("products/detail", $data["product_image"]);
                }
            } catch (\Throwable $th) {
            }

            $result = [
                "product_id" => $data["product_id"],
                "category_id" => $data["category_id"] ?? null,
                "product_varian_id" => $data["product_varian_id"] ?? null,
                "product_image" => $image,
                "material" => $data["material"] ?? null,
                "unit" => $data["unit"] ?? null,
                "stock" => $data["stock"] ?? 0,
                "capacity" => $data["capacity"] ?? 0,
                "weight" => $data["weight"] ?? 0,
                "density" => $data["density"] ?? 0,
                "price" => $data["price"] ?? 0,
                "price_discount" => $data["price_discount"] ?? 0,
                "product_code" => $data["product_code"] ?? null,
                "variant_name" => $data["variant_name"] ?? null,
            ];
            return $result;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }

    public function dataProductDetailUpdate(array $data, ProductDetail $product)
    {
        try {
            $image = $product->image;
            try {
                if (isset($data["image"])) {
                    if ($image) $this->remove($product->image);

                    $image = $this->upload("products/detail", $data["product_image"]);
                }
            } catch (\Throwable $th) {
            }

            return [
                "product_id" => $data["product_id"],
                "category_id" => $data["category_id"] ?? null,
                "product_varian_id" => $data["product_varian_id"] ?? null,
                "product_image" => $image,
                "material" => $data["material"] ?? null,
                "unit" => $data["unit"] ?? null,
                "stock" => isset($data["stock"]) ? $data["stock"] : 0,
                "capacity" => $data["capacity"] ?? 0,
                "weight" => $data["weight"] ?? 0,
                "density" => $data["density"] ?? 0,
                "price" => $data["price"] ?? 0,
                "price_discount" => $data["price_discount"] ?? 0,
                "product_code" => $data["product_code"] ?? null
            ];
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }
}
