<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Repositories\Master\ProductStockRepository;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportProductRequest;
use App\Imports\ProductImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ProductExportImportController extends Controller
{
    public function import(ImportProductRequest $request, ProductStockRepository $productStock)
    {
        $request->validated();

        try {
            Excel::import(new ProductImport($productStock), $request->file('file'));

            return BaseResponse::Create("Impor berhasil.", null);
        } catch (ValidationException $e) {
            return BaseResponse::Error("Validasi Excel gagal.", $e->failures());
        } catch (\Throwable $th) {
            return BaseResponse::Error("Terjadi kesalahan saat import produk", [
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile()
            ]);
        }
    }
}
