<?php 

namespace App\Services\Master;

use Error;
use Illuminate\Support\Facades\Log;

class ProductService{
    
    public function __construct()
    {
        
    }

    public function dataProduct(array $data)
    {
        try{
            $image = null;

            return [
                "store_id" => $data["store_id"],
                "name" => $data["name"],
                "image" => $image,
                "unit_type" => $data["unit_type"],
                "qr_code" => $data["qr_code"]
            ];
        }catch(\Throwable $th){
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }
}