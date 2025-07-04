<?php

namespace App\Services;

use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Interfaces\Master\UnitInterface;
use App\Contracts\Interfaces\ProductBlendDetailInterface;
use App\Contracts\Interfaces\ProductBlendInterface;
use App\Http\Requests\ProductBlendRequest;
use App\Models\ProductStock;

class ProductBlendService
{
    private UnitInterface $unit;
    private ProductBlendDetailInterface $productBlendDetail;
    private ProductBlendInterface $productBlend;
    private ProductDetailInterface $productDetail;
    private ProductStockInterface $productStock;

    public function __construct(
        UnitInterface $unit,
        ProductBlendDetailInterface $productBlendDetail,
        ProductBlendInterface $productBlend,
        ProductDetailInterface $productDetail,
        ProductStockInterface $productStock
    ) {
        $this->unit = $unit;
        $this->productBlendDetail = $productBlendDetail;
        $this->productBlend = $productBlend;
        $this->productDetail = $productDetail;
        $this->productStock = $productStock;
    }

    public function store(ProductBlendRequest $request)
    {
        $data = $request->validated();
        
        foreach ($data['product_blend'] as $productBlend) {
            $data['store_product_blend'] = [
                'store_id' => auth()->user()->store_id,
                'warehouse_id' => auth()->user()->warehouse_id,
                'unit_id' => $productBlend['unit_id'],
                'result_stock' => $productBlend['result_stock'],
                'product_detail_id' => $productBlend['product_detail_id'],
                'date' => now(),
                'description' => $productBlend['description'],
            ];
        }

        return $data;
    }

    public function storeBlendDetail(array $data)
    {
        foreach ($data['product_blend'] as $productBlend) {
            foreach ($productBlend['product_blend_details'] as $detail) {
                $this->productBlendDetail->store([
                    'product_blend_id' => $data['product_blend_id'],
                    'product_detail_id' => $detail['product_detail_id'],
                    'used_stock' => $detail['used_stock'],
                ]);

                // Kurangi stok dari tabel product_stocks
                $stock = $this->productStock->getFromProductDetail($detail['product_detail_id']);

                if (!$stock || $stock->stock < $detail['used_stock']) {
                    $productDetail = $this->productDetail->find($detail['product_detail_id']);
                    $productName = $productDetail?->product?->name ?? 'Produk Tidak Dikenal';
                    throw new \Exception("Stok tidak cukup untuk {$productName}");
                }

            }
        }
    }

    public function storeProductStock(array $data)
    {
        foreach ($data['product_blend'] as $productBlend) {
            $productDetail = $this->productDetail->find($productBlend['product_detail_id']);

            // Cari stok berdasarkan warehouse dan product_detail_id
            $stock = $this->productStock->getFromProductDetail($productBlend['product_detail_id']);

            
                $this->productStock->store([
                    'outlet_id' => auth()->user()->outlet_id,
                    'warehouse_id' => auth()->user()->warehouse_id,
                    'product_id' => $productDetail->product_id,
                    'product_detail_id' => $productDetail->id,
                    'stock' => $productBlend['result_stock'],
                ]);
            
        }

        return $data;
    }
}
