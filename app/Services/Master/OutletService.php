<?php 

namespace App\Services\Master;

use App\Models\Outlet;
use App\Traits\UploadTrait;
use Error;
use Illuminate\Support\Facades\Log;

class OutletService{

    use UploadTrait;
    
    public function __construct()
    {
        
    }

    public function dataOutlet(array $data)
    {
        try{
            $image = null;
            try{
                if(isset($data["image"])) {
                    $image = $this->upload("outlets", $data["image"]);
                }
            }catch(\Throwable $th){ }

            $result = [
                "store_id" => auth()?->user()?->store?->id ?? auth()?->user()?->store_id,
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

    public function dataOutletUpdate(array $data, Outlet $outlet)
    {
        try{
            $image = $outlet->image;
            try{
                if(isset($data["image"])) {
                    if($image) $this->remove($outlet->image);
                    
                    $image = $this->upload("outlets", $data["image"]);
                }
            }catch(\Throwable $th){ }

            return [
                "store_id" => auth()?->user()?->store?->id ?? auth()?->user()?->store_id,
                "name" => $data["name"],
                "image" => $image,
                "address" => $data["address"] ?? $outlet->address,
                "telp" => $data["telp"] ?? $outlet->telp,
            ];
        }catch(\Throwable $th){
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }

}