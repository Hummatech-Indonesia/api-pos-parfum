<?php

namespace App\Services\Master;

use App\Models\Pengeluaran;
use App\Traits\UploadTrait;
use Error;
use Illuminate\Support\Facades\Log;

class PengeluaranService
{

    use UploadTrait;

    public function __construct() {}

    public function dataPengeluaran(array $data)
    {
        try {
            $image = null;
            try {
                if (isset($data["image"])) {
                    $image = $this->upload("pengeluaran", $data["image"]);
                } else {
                    $image = "default/Default.jpeg";
                }
            } catch (\Throwable $th) {
                $image = "default/Default.jpeg";
            }

            $result = [
                "nama_pengeluaran" => $data["nama_pengeluaran"],
                "nominal_pengeluaran" => $data["nominal_pengeluaran"],
                "deskripsi" => $data["deskripsi"],
                "tanggal_pengeluaran" => now(),
                "nominal_pengeluaran" => $data["nominal_pengeluaran"],
                "kategori_pengeluaran_id" => $data["kategori_pengeluaran_id"],
                "image" => $image,
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

    public function dataPengeluaranUpdate(array $data, Pengeluaran $pengeluaran)
    {
        try {
            $image = $pengeluaran->image;
            try {
                if (isset($data["image"])) {
                    if ($image) $this->remove($pengeluaran->image);

                    $image = $this->upload("pengeluaran", $data["image"]);
                }
            } catch (\Throwable $th) {
            }

            $result = [
                "nama_pengeluaran" => $data["nama_pengeluaran"],
                "nominal_pengeluaran" => $data["nominal_pengeluaran"],
                "deskripsi" => $data["deskripsi"],
                "tanggal_pengeluaran" => $pengeluaran->tanggal_pengeluaran,
                "nominal_pengeluaran" => $data["nominal_pengeluaran"],
                "kategori_pengeluaran_id" => $data["kategori_pengeluaran_id"],
                "image" => $image,
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
