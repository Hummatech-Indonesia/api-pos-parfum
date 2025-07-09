<?php

namespace App\Imports;

use App\Contracts\Repositories\Master\ProductStockRepository;
use App\Imports\Rules\ProductImportRules;
use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Services\ProductImportService;
use Throwable;

class ProductImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $productStock;
    protected $mappingService;

    public function __construct(ProductStockRepository $productStock)
    {
        $this->productStock = $productStock;
        $this->mappingService = new ProductImportService();
    }

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();

        try {
            $grouped = $rows->groupBy('name');

            foreach ($grouped as $name => $groupedRows) {
                $firstRow = $groupedRows->first()->toArray();

                $productData = $this->mappingService->mapProduct($firstRow);
                $product = Product::create($productData);

                foreach ($groupedRows as $row) {
                    $rowArray = $row->toArray();

                    $detailData = $this->mappingService->mapProductDetail($rowArray, $product->id);
                    $detail = ProductDetail::create($detailData);

                    $stockData = $this->mappingService->mapStock($rowArray, $product->id, $detail->id);
                    $this->productStock->store($stockData);
                }
            }

            DB::commit();
        } catch (Throwable $th) {
            DB::rollBack();
            throw new \Exception("Gagal import produk: " . $th->getMessage());
        }
    }

    public function rules(): array
    {
        return ProductImportRules::rules();
    }

    public function customValidationMessages()
    {
        return ProductImportRules::messages();
    }
}
