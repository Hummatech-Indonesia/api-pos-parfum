<?php 

namespace App\Services\Master;

use App\Models\Product;
use App\Traits\UploadTrait;
use Error;
use Illuminate\Support\Facades\Log;

class ProductService{

    use UploadTrait;
    
    public function __construct()
    {
        
    }

    public function dataProduct(array $data)
    {
        try{
            $image = null;
            try{
                if(isset($data["image"])) {
                    $image = $this->upload("products", $data["image"]);
                } else {
                    $image = "default/Default.jpeg";
                }
            }catch(\Throwable $th){
                $image = "default/Default.jpeg";
            }

            $result = [
                "store_id" => $data["store_id"],
                "name" => $data["name"],
                "image" => $image,
                "unit_type" => $data["unit_type"],
                "qr_code" => $data["qr_code"],
                "category_id" => $data["category_id"],
                "description" => $data["description"] ?? null,

            ];
            return $result;
        }catch(\Throwable $th){
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }

    public function dataProductUpdate(array $data, Product $product)
    {
        try{
            $image = $product->image;
            try{
                if(isset($data["image"])) {
                    if($image) $this->remove($product->image);
                    
                    $image = $this->upload("products", $data["image"]);
                }
            }catch(\Throwable $th){ }

            return [
                "store_id" => $data["store_id"],
                "name" => $data["name"],
                "image" => $image,
                "unit_type" => $data["unit_type"],
                "qr_code" => $data["qr_code"],
                "category_id" => $data["category_id"],
                "description" => $data["description"] ?? null,
            ];
        }catch(\Throwable $th){
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }

    public function formatProductListResponse($products)
    {
        return $products->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'image' => $item->image,
                'details_sum_stock' => $item->details_sum_stock,
                'category' => [
                    'name' => $item->category->name ?? null
                ],
                'details' => $item->details->map(function ($detail) {
                    return [
                        'stock' => $detail->stock,
                        'price' => $detail->price,
                        'variant_name' => null,
                        'product_code' => $detail->product_code,
                        'product_image' => $detail->image,
                        'transaction_details_count' => $detail->transaction_details_count ?? 0,
                        'category' => [
                            'name' => $detail->category->name ?? null
                        ]
                    ];
                })
            ];
        });
    }

    public function formatProductDetailResponse(Product $product): array
    {
        return [
            "name" => $product->name,
            "image" => $product->image,
            "category_id" => $product->category_id,
            "description" => $product->description,
            "details" => $product->details->map(function ($detail) {
                return [
                    "stock" => $detail->stock,
                    "price" => $detail->price,
                    "variant_name" => null,
                    "product_code" => $detail->product_code,
                    "product_image" => $detail->image,
                    "transaction_details_count" => $detail->transaction_details_count ?? 0,
                    "category" => [
                        "name" => $detail->category->name ?? null
                    ]
                ];
            })->toArray()
        ];
    }


}