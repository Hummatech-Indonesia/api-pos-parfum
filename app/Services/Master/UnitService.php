<?php 

namespace App\Services\Master;

use Error;
use Illuminate\Support\Facades\Log;

class UnitService{

    public function __construct()
    {
        
    }

    public function dataUnit(array $data)
    {
        try{
            $result = [
                "name" => $data["name"],
                "code" => $data["code"],
                "store_id" => auth()->user()->store_id,
            ];
            return $result;
        }catch(\Throwable $th){
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }

}