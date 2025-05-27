<?php

namespace App\Services;

use App\Contracts\Interfaces\Master\ProductVarianInterface;
use App\Contracts\Interfaces\Master\UnitInterface;
use App\Contracts\Interfaces\ProductBlendDetailInterface;
use App\Contracts\Interfaces\ProductBlendInterface;
use App\Http\Requests\ProductBlendRequest;

class ProductBlendService
{
    private UnitInterface $unit;
    private ProductBlendDetailInterface $productBlendDetail;
    private ProductVarianInterface $productVarian;
    private ProductBlendInterface $productBlend;

    public function __construct(UnitInterface $unit, ProductBlendDetailInterface $productBlendDetail, ProductVarianInterface $productVarian, ProductBlendInterface $productBlend)
    {
        $this->unit = $unit;
        $this->productBlendDetail = $productBlendDetail;
        $this->productVarian = $productVarian;
        $this->productBlend = $productBlend;
    }

    public function store(ProductBlendRequest $request)
    {
        $data = $request->validated();
        // dd($data);

        // $unit_id = null;
        // if ($unit = $this->unit->cekUnit($productBlend['unit_name'], $productBlend['code'])) {
        //     $unit_id = $unit->id;
        // } else {
        //     $unit = $this->unit->store([
        //         'name' => $productBlend['unit_name'],
        //         'code' => $productBlend['code']
        //     ]);

        //     $unit_id = $unit->id;
        // }

        // unset($productBlend['unit_name']);
        // unset($productBlend['code']);
        // dd($productBlend);

        foreach ($data['product_blend'] as $productBlend) {
            // $this->productBlend->store([
            //     'store_id' => auth()->user()->store_id,
            //     'warehouse_id' => auth()->user()->warehouse_id,
            //     'result_stock' => $productBlend['result_stock'],
            //     'unit_id' => $productBlend['unit_id'],
            //     'date' => now(),
            // ]);

            $data['store_product_blend'] = [
                'store_id' => auth()->user()->store_id,
                'warehouse_id' => auth()->user()->warehouse_id,
                'result_stock' => $productBlend['result_stock'],
                'unit_id' => $productBlend['unit_id'],
                'date' => now(),
            ];
        }
        // dd($data);

        // foreach ($data['product_blend'] as $productBlend) {
        // }

        // $data['product_blend'] = [
        //     'store_id' => auth()->user()->store_id,
        //     'warehouse_id' => auth()->user()->warehouse_id,
        //     'result_stock' => $data['result_stock'],
        //     'unit_id' => $unit_id,
        //     'date' => now(),
        // ];

        return $data;
    }

    public function storeBlendDetail(array $data)
    {
        // $unit_id = null;
        // if ($unit = $this->unit->cekUnit($data['product_blend']['unit_name'], $data['product_blend']['code'])) {
        //     $unit_id = $unit->id;
        // } else {
        //     $unit = $this->unit->store([
        //         'name' => $data['product_blend']['unit_name'],
        //         'code' => $data['code']
        //     ]);

        //     $unit_id = $unit->id;
        // }

        // dd($data);
        foreach ($data['product_blend'] as $productBlend) {
            foreach ($productBlend['product_blend_details'] as $productBlendDetail) {
                $this->productBlendDetail->store([
                    'product_blend_id' => $data['product_blend_id'],
                    'product_detail_id' => $productBlendDetail['product_detail_id'],
                    'used_stock' => $productBlendDetail['used_stock'],
                    'unit_id' => $productBlend['unit_id'],
                ]);
            }
        }
        // dd($data);
    }

    public function storeProduct(ProductBlendRequest $request)
    {
        $data = $request->validated();

        foreach ($data['product_blend'] as $productBlend) {
            $data['image'] = null;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $data['image'] = $request->file('image')->store('public/product');
            }

            return [
                'store_id' => auth()->user()->store_id,
                'name' => $data['name'],
                'image' => $data['image'],
                'unit_type' => $productBlend['unit_type'],
            ];
        }
    }

    public function storeVarian(array $data)
    {
        foreach ($data['product_blend'] as $productBlend) {
            $varian_id = null;
            if ($varian = $this->productVarian->where(['name' => $productBlend['varian_name']])) {
                $varian_id = $varian->id;
            } else {
                $varian_id = $this->productVarian->store(['name' => $productBlend['varian_name']])->id;
            }

            $data['product_varian_id'] = $varian_id;
            $data['store_id'] = auth()->user()->store_id;
            return $data;
        }
    }
}
