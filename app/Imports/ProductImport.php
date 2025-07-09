<?php

namespace App\Imports;

use App\Contracts\Repositories\Master\ProductStockRepository;
use App\Imports\Rules\ProductImportRules;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Unit;
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

            $category = Category::where('is_delete',0)
            ->where('store_id', auth()->user()->store_id)
            ->when(auth()->user()?->hasRole('outlet'), fn($q) => $q->where('outlet_id',auth()->user()?->outlet_id))
            ->when(auth()->user()?->hasRole('warehouse'), fn($q) => $q->where('warehouse_id',auth()->user()?->warehouse_id))
            ->get();

            $unit = Unit::where('store_id', auth()->user()?->store_id)->get();

            $categoryOriginal = collect($category->all());
            $unitOriginal = collect($unit->all());

            foreach ($grouped as $name => $groupedRows) {
                $firstRow = $groupedRows->first()->toArray();

                $categorySelect = $categoryOriginal->where('name', 'like', '%'.$firstRow['category_name'].'%')->first();
                if (!$categorySelect) {
                    $firstRow['category_id'] = $categoryOriginal->first()?->id;
                } else {
                    $firstRow['category_id'] = $categorySelect->id;
                }

                $productData = $this->mappingService->mapProduct($firstRow);
                $product = Product::create($productData);
                
                foreach ($groupedRows as $row) {
                    $rowArray = $row->toArray();
                    $unitSelect = $unitOriginal->firstWhere('code', $rowArray['unit']);
                    if (!$unitSelect) {
                        $rowArray['unit'] = $unitOriginal->first()?->id;
                    } else {
                        $rowArray['unit'] = $unitSelect->id;
                    }
                    
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
