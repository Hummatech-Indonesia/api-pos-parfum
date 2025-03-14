<?php 

namespace App\Services\Master;

use App\Models\Warehouse;
use App\Traits\UploadTrait;
use Error;
use Illuminate\Support\Facades\Log;

class WarehouseService{

    use UploadTrait;
    
    public function __construct()
    {
        
    }

    public function dataWarehouse(array $data)
    {
        try{
            $image = null;
            try{
                if(isset($data["image"])) {
                    $image = $this->upload("warehouses", $data["image"]);
                }
            }catch(\Throwable $th){ }

            $result = [
                "store_id" => auth()?->user()?->store?->id,
                "name" => $data["name"],
                "image" => $image,
                "address" => $data["address"] ?? null,
                "telp" => $data["telp"] ?? null,
            ];
            return $result;
        }catch(\Throwable $th){
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }

    public function dataWarehouseUpdate(array $data, Warehouse $warehouse)
    {
        try{
            $image = $warehouse->image;
            try{
                if(isset($data["image"])) {
                    if($image) $this->remove($warehouse->image);
                    
                    $image = $this->upload("warehouses", $data["image"]);
                }
            }catch(\Throwable $th){ }

            return [
                "store_id" => auth()?->user()?->store?->id,
                "name" => $data["name"],
                "image" => $image,
                "address" => $data["address"] ?? $warehouse->address,
                "telp" => $data["telp"] ?? $warehouse->telp,
            ];
        }catch(\Throwable $th){
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }

}