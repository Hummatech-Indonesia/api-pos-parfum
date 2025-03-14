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
        // if ($request->search) $payload["search"] = $request->search;
        if (auth()->user()->warehouse_id) $payload["warehouse_id"] = auth()->user()->warehouse_id; 

        $data = $this->stockRequest->customPaginate($per_page, $page, $payload)->toArray();

        $result = $data["data"];
        unset($data["data"]);

        return BaseResponse::Paginate('Berhasil mengambil list stock request !', $result, $data);
    }

    public function listStockRequest(Request $request)
    {
        try {
            $payload = [];

            if ($request->has('is_delete')) $payload["is_delete"] = $request->is_delete;

            $data = $this->stockRequest->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data stock request", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
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

        DB::beginTransaction();
        try {
            // Check if product_detail exists
            $check = $this->productDetail->show($data["product_detail_id"])->exists();
            if (!$check) return BaseResponse::Notfound("Tidak ada data product detail!");

            // Check if outlet_id is null and the user has an outlet_id
            if (auth()->user()->outlet_id === null) {
                return BaseResponse::Error("User tidak punya Outlet", null);
            }

            // Assign user_id and outlet_id from authenticated user
            $data["user_id"] = auth()->user()->id;
            $data["outlet_id"] = auth()->user()->outlet_id;

            // Store the stock request
            $result_product = $this->stockRequest->store($data);

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat stock request', $result_product);
        } catch (\Throwable $th) {
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
