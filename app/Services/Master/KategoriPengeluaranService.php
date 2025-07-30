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
                "nama" => $data["nama"]
            ];
            if(auth()->user()->warehouse_id) {
                $result['warehouse_id'] = auth()->user()->warehouse_id;
            } else if (auth()->user()->outlet_id) {
                $result['outlet_id'] = auth()->user()->outlet_id;
            }

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
                "nama" => $data["nama"]
            ];
            if(auth()->user()->warehouse_id) {
                $result['warehouse_id'] = auth()->user()->warehouse_id;
            } else if (auth()->user()->outlet_id) {
                $result['outlet_id'] = auth()->user()->outlet_id;
            }

            return $result;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }
}
