<?php

namespace App\Services\Master;

use App\Contracts\Interfaces\Master\ProductBundlingDetailInterface;
use App\Contracts\Interfaces\Master\ProductBundlingInterface;
use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Models\Product;
use App\Models\ProductBundling;
use App\Models\ProductBundlingDetail;
use App\Models\ProductDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductBundlingService
{
    protected $productRepo;
    protected $bundlingRepo;
    protected $bundlingDetailRepo;
    protected $productDetailRepo;

    public function __construct(
        ProductInterface $productRepo,
        ProductBundlingInterface $bundlingRepo,
        ProductBundlingDetailInterface $bundlingDetailRepo,
        ProductDetailInterface $productDetailRepo,
    ) {
        $this->productRepo = $productRepo;
        $this->bundlingRepo = $bundlingRepo;
        $this->bundlingDetailRepo = $bundlingDetailRepo;
        $this->productDetailRepo = $productDetailRepo;
    }

    public function storeBundling(array $productData, $bundlingData, $detailsData)
    {
        return DB::transaction(function () use ($productData, $bundlingData, $detailsData) {
            $product = $this->productRepo->store($productData);
            
            $bundlingData['product_id'] = $product->id;
            $bundlingData['category_id'] = $product->category_id;

            $bundling = $this->bundlingRepo->store($bundlingData);

            foreach ($detailsData as $detail) {
                $productDetail = $this->productDetailRepo->store([
                    'id' => uuid_create(),
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    ...$detail['product_detail'],
                ]);

                $this->bundlingDetailRepo->store([
                    'product_bundling_id' => $bundling->id,
                    'product_detail_id' => $productDetail->id,
                    'unit' => $detail['unit'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                ]);
            }

            return $this->bundlingRepo->show($bundling->id)->load('details');
        });
    }

    public function updateBundling(ProductBundling $bundling, array $data): ProductBundling
    {
        return DB::transaction(function () use ($bundling, $data) {
            
            foreach ($bundling->details as $detail) {
                $this->bundlingDetailRepo->delete($detail->id);
            }

            foreach ($data['details'] as $detail) {
                $this->bundlingDetailRepo->store([
                    'product_bundling_id' => $bundling->id,
                    'product_detail_id' => $detail['product_detail_id'],
                    'unit' => $detail['unit'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                ]);
            }

            return $this->bundlingRepo->show($bundling->id)->load('details');
        });
    }

    public function deleteBundling(ProductBundling $bundling): void
    {
        DB::transaction(function () use ($bundling) {
            foreach ($bundling->details() as $detail) {
                $this->bundlingDetailRepo->delete($detail->id);
            }

            $this->bundlingRepo->delete($bundling->id);
        });
    }

    public function restoreBundling(string $id): ProductBundling
    {
        return DB::transaction(function () use ($id) {
            $this->bundlingRepo->restore($id);

            $bundling = $this->bundlingRepo->show($id);
            foreach ($bundling->details()->withTrashed()->get() as $detail) {
                $this->bundlingDetailRepo->restore($detail->id);
            }

            return $bundling->load('details');
        });
    }

}
