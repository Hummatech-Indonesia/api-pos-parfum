<?php

namespace App\Services\Master;

use App\Models\KategoriPengeluaran;
use Error;
use Illuminate\Support\Facades\Log;

class KategoriPengeluaranService
{
    public function dataKategoriPengeluaran(array $data)
    {
        try {
            $result = [
                "nama" => $data["nama"],
                "outlet_id" => $data["outlet_id"],
                "warehouse_id" => $data["warehouse_id"],
            ];
            return $result;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }

    public function dataKategoriPengeluaranUpdate(array $data, KategoriPengeluaran $outlet)
    {
        try {
            $result = [
                "nama" => $data["nama"],
                "outlet_id" => $data["outlet_id"],
                "warehouse_id" => $data["warehouse_id"],
            ];

            return $result;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }
}
