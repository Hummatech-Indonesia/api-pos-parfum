<?php

namespace App\Imports;

use App\Contracts\Repositories\Master\ProductStockRepository;
use App\Imports\Rules\ProductImportRules;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Services\ProductImportService;

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

        $grouped = $rows->groupBy('name');

        foreach ($grouped as $name => $groupedRows) {
            $firstRow = $groupedRows->first()->toArray();

            $category = Category::where('name', $firstRow['category_name'])->first();
            if (!$category) {
                throw new \Exception("Kategori '{$firstRow['category_name']}' tidak ditemukan.");
            }

            $productData = $this->mappingService->mapProduct($firstRow, $category->id);
            $product = Product::create($productData);

            foreach ($groupedRows as $row) {
                $rowArray = $row->toArray();

                $detailData = $this->mappingService->mapProductDetail($rowArray, $product->id, $category->id);
                $detail = ProductDetail::create($detailData);

                $stockData = $this->mappingService->mapStock($rowArray, $product->id, $detail->id);
                $this->productStock->store($stockData);
            }
        }

        DB::commit();
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
