<?php

namespace App\Services\Master;

use App\Models\Product;
use App\Traits\UploadTrait;
use Error;
use Illuminate\Support\Facades\Log;

class ProductService
{

    use UploadTrait;

    public function __construct() {}

    public function dataProduct(array $data)
    {
        try {
            $image = null;
            try {
                if (isset($data["image"])) {
                    $image = $this->upload("products", $data["image"]);
                }
            } catch (\Throwable $th) {
            }

            $result = [
                "store_id" => $data["store_id"],
                "name" => $data["name"],
                "image" => $image,
                "user_id" => auth()->user()->id,
                "unit_type" => $data["unit_type"],
                "qr_code" => $data["qr_code"],
                "category_id" => $data["category_id"],
                "description" => $data["description"] ?? null,
                "warehouse_id" => $data["warehouse_id"] ?? null,
                "outlet_id" => $data["outlet_id"] ?? null,

            ];
            return $result;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }

    public function dataProductUpdate(array $data, Product $product)
    {
        try {
            $image = $product->image;
            try {
                if (isset($data["image"])) {
                    if ($image) $this->remove($product->image);

                    $image = $this->upload("products", $data["image"]);
                }
            } catch (\Throwable $th) {
            }

            return [
                "store_id" => $data["store_id"],
                "name" => $data["name"],
                "image" => $image,
                "user_id" => auth()->user()->id,
                "unit_type" => $data["unit_type"],
                "qr_code" => $data["qr_code"],
                "category_id" => $data["category_id"],
                "description" => $data["description"] ?? null,
                "warehouse_id" => $data["warehouse_id"] ?? null,
                "outlet_id" => $data["outlet_id"] ?? null,
            ];
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }
    public function injectDensityToDetails(array $data): array
    {
        if (isset($data['density']) && isset($data['product_details'])) {
            $data['product_details'] = collect($data['product_details'])->map(function ($detail) use ($data) {
                $detail['density'] = $data['density'];
                return $detail;
            })->toArray();
        }

        return $data;
    }
}
