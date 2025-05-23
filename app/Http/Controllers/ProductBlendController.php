<?php

namespace App\Http\Controllers;

use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Interfaces\ProductBlendInterface;
use App\Helpers\BaseResponse;
use App\Http\Requests\ProductBlendRequest;
use App\Models\ProductBlend;
use App\Services\ProductBlendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBlendController extends Controller
{
    private ProductBlendService $productBlendService;
    private ProductBlendInterface $productBlend;
    private ProductInterface $product;
    private ProductDetailInterface $productDetail;

    public function __construct(ProductBlendService $productBlendService, ProductBlendInterface $productBlend, ProductInterface $product, ProductDetailInterface $productDetail)
    {
        $this->productBlendService = $productBlendService;
        $this->productBlend = $productBlend;
        $this->product = $product;
        $this->productDetail = $productDetail;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $page = $request->page ?? 1;

        $payload = [];

        if ($request->search) $payload["search"] = $request->search;

        try {
            $data = $this->productBlend->customPaginate($per_page, $page, $payload, ['productBlendDetails'])->toArray();

            $result = $data['data'];
            unset($data['data']);

            return BaseResponse::Paginate('Berhasil mengambil list data product blend!', $result, $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductBlendRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $this->productBlendService->store($request);

            $productBlend = $data['store_product_blend']; // ambil data campuran pertama
            $data['product_blend_id'] = $this->productBlend->store($productBlend)->id;

            $this->productBlendService->storeBlendDetail($data);
            $product = $this->productBlendService->storeProduct($request);
            $product_id = $this->product->store($product)->id;

            $this->productBlend->update($data['product_blend_id'], ['product_id' => $product_id]);

            $data = $this->productBlendService->storeVarian($data);

            foreach ($data['product_blend'] as $productBlend) {
                $this->productDetail->store([
                    'product_id' => $product_id,
                    'category_id' => $productBlend['category_id'],
                    'product_varian_id' => $data['product_varian_id'],
                    'price' => $productBlend['price'],
                ]);
            }

            //flow ketika nambh stok sama ngurangi stok

            DB::commit();
            return BaseResponse::Ok("Produk berhasil dibuat", $data);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function blend(ProductBlend $productBlend)
    {
        DB::beginTransaction();

        try {
            $blendDetails = $productBlend->productBlendDetails;

            foreach ($blendDetails as $detail) {
                $productDetail = $detail->productDetail;
                if ($productDetail->stock < $detail->used_stock) {
                    return BaseResponse::Error("Stok bahan '{$detail->productDetail->product->name}' tidak cukup untuk melakukan pencampuran.", null);
                } else {
                    $productDetail->stock -= $detail->used_stock;
                    $productDetail->save();

                    $productHasil = $productBlend->product->details;
                    foreach ($productHasil as $productDetailHasil) {
                        $productDetailHasil->stock += $productBlend->result_stock;
                        $productDetailHasil->save();
                    }
                }

                DB::commit();

                return BaseResponse::Ok('Pencampuran berhasil dilakukan!', null);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
