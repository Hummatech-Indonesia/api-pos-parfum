<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;
use App\Enums\UploadDiskEnum;
use App\Helpers\BaseResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Master\ProductService;
use Illuminate\Support\Facades\Validator;
use App\Services\Master\ProductDetailService;
use App\Http\Requests\Master\ProductDetailRequest;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Interfaces\Master\ProductDetailInterface;

class ProductDetailController extends Controller
{
    private ProductInterface $product;
    private ProductDetailInterface $productDetail;
    private ProductService $productService;
    private ProductStockInterface $productStock;
    private ProductDetailService $productDetailService;

    public function __construct(
        ProductInterface $product,
        ProductDetailInterface $productDetail,
        ProductService $productService,
        ProductStockInterface $productStock,
        ProductDetailService $productDetailService
    ) {
        $this->product = $product;
        $this->productDetail = $productDetail;
        $this->productService = $productService;
        $this->productStock = $productStock;
        $this->productDetailService = $productDetailService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        $payload = [
            "is_delete" => 0
        ];

        // check query filter
        if ($request->search) $payload["search"] = $request->search;
        if ($request->is_delete) $payload["is_delete"] = $request->is_delete;

        $data = $this->productDetail->customPaginate($per_page, $page, $payload)->toArray();

        $result = $data["data"];
        unset($data["data"]);

        return BaseResponse::Paginate('Berhasil mengambil list data product !', $result, $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductDetailRequest $request)
    {
        $data = $request->validated();


        DB::beginTransaction();
        try {
            $mapping = $this->productDetailService->dataProductDetail($data);
            $result_product = $this->productDetail->store($mapping);

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat product ', $result_product);
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
        $check_product = $this->productDetail->show($id);
        if (!$check_product) return BaseResponse::Notfound("Tidak dapat menemukan data product !");

        return BaseResponse::Ok("Berhasil mengambil detail product !", $check_product);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductDetailRequest $request, string $id)
    {

        $data = $request->validated();

        DB::beginTransaction();
        try {
            $productDetail = $this->productDetail->show($id);
            $mapping = $this->productDetailService->dataProductDetailUpdate($data, $productDetail);
            $result_product = $this->productDetail->update($id, $mapping);

            DB::commit();
            return BaseResponse::Ok('Berhasil update product', $result_product);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $check = $this->productDetail->checkActive($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data product detail!");

        if (
            $check->discountVouchers()->exists() ||
            $check->auditDetails()->exists() ||
            $check->productBlendDetail()->exists()
        ) {
            return BaseResponse::Error("Produk tidak dapat dihapus karena masih digunakan dalam relasi lain.", null);
        }

        DB::beginTransaction();
        try {
            $this->productDetail->delete($id);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listProduct(Request $request)
    {
        try {
            $payload = [];

            if ($request->product_id) $payload['product_id'] = $request->product_id;
            $data = $this->productDetail->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data product ", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function stockProduct(Request $request)
    {
        $payload = [];
        try {
            if ($request->warehouse_id) $payload["warehouse_id"] = $request->warehouse_id;
            if ($request->product_detail_id) $payload["product_detail_id"] = $request->product_detail_id;
            if ($request->outlet_id) $payload["outlet_id"] = $request->outlet_id;

            if ($request->page && $request->per_page) $data = $this->productStock->customPaginate($request->per_page, $request->page, $payload);
            else $data = $this->productStock->customQuery($payload);

            return BaseResponse::Ok("Berhasil mengambil data product ", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
