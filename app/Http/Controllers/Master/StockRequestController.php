<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\StockRequestInterface;
use App\Helpers\BaseResponse;
use App\Http\Requests\Master\StockRequestRequest;
use App\Http\Controllers\Controller;
use App\Models\StockRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockRequestController extends Controller
{
    private $stockRequest;
    private $productDetail;

    public function __construct(
        StockRequestInterface $stockRequest,
        ProductDetailInterface $productDetail
    ) {
        $this->stockRequest = $stockRequest;
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

        // check query filter
        if ($request->search) $payload["search"] = $request->search;
        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        $data = $this->stockRequest->customPaginate($per_page, $page, $payload)->toArray();

        $result = $data["data"];
        unset($data["data"]);

        return BaseResponse::Paginate('Berhasil mengambil list data product !', $result, $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StockRequestRequest $request)
    {
        $data = $request->validated();

        $check = $this->productDetail->customQuery(["product_id" => $data["product_id"], "id" => $data["product_detail_id"]])->exists();
        if (!$check) return BaseResponse::Notfound("Tidak ada data product detail!");

        DB::beginTransaction();
        try {

            $result_product = $this->stockRequest->store($data);

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat stock request ', $result_product);
        }catch(\Throwable $th){
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(StockRequest $stockRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StockRequest $stockRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StockRequest $stockRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockRequest $stockRequest)
    {
        //
    }
}
