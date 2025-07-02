<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Interfaces\Master\StockRequestInterface;
use App\Contracts\Interfaces\Master\StockRequestDetailInterface;
use App\Helpers\BaseResponse;
use App\Http\Requests\Master\StockRequestRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StockRequestUpdateRequest;
use App\Http\Resources\StockRequestDetailResource;
use App\Http\Resources\StockRequestResource;
use App\Models\StockRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockRequestController extends Controller
{
    private $stockRequest;
    private $stockRequestDetail;
    private $productDetail;
    private ProductStockInterface $productStock;

    public function __construct(
        StockRequestInterface $stockRequest,
        StockRequestDetailInterface $stockRequestDetail,
        ProductDetailInterface $productDetail,
        ProductStockInterface $productStock
    ) {
        $this->stockRequest = $stockRequest;
        $this->stockRequestDetail = $stockRequestDetail;
        $this->productDetail = $productDetail;
        $this->productStock = $productStock;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        $payload = [];

        if (auth()->user()->warehouse_id) {
            $payload["warehouse_id"] = auth()->user()->warehouse_id;
        }

        $data = $this->stockRequest->customPaginate($per_page, $page, $payload);

        $result = StockRequestResource::collection($data->items());

        $pagination = $data->toArray();
        unset($pagination['data']);

        return BaseResponse::Paginate('Berhasil mengambil list stock request !', $result, $pagination);
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

            if (auth()->user()->outlet_id === null) {
                return BaseResponse::Error("User tidak punya Outlet", null);
            }

            $productDetails = [];
            foreach ($data['requested_stock'] as $item) {
                $productDetailId = $item['variant_id'];
                $check = $this->productDetail->show($productDetailId);

                if (!$check) {
                    return BaseResponse::Notfound("Product detail dengan ID {$productDetailId} tidak ditemukan!");
                }

                $productDetails[] = [
                    'product_detail_id' => $productDetailId,
                    'requested_stock' => $item['requested_stock']
                ];
            }

            $stockRequestData = [
                'user_id' => auth()->user()->id,
                'outlet_id' => auth()->user()->outlet_id,
                'warehouse_id' => $data['warehouse_id'],
                'product_detail_id' => null, // nullable
            ];

            $stockRequest = $this->stockRequest->store($stockRequestData);

            foreach ($productDetails as $detail) {
                $this->stockRequestDetail->store([
                    'stock_request_id' => $stockRequest->id,
                    'product_detail_id' => $detail['product_detail_id'],
                    'requested_stock' => $detail['requested_stock'],
                ]);
            }

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat stock request', $stockRequest);
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
        try {
            $stockRequest = $this->stockRequest->show($id);

            if (!$stockRequest) {
                return BaseResponse::Notfound("Stock request tidak ditemukan");
            }

            $data = [
                'id' => $stockRequest->id,
                'outlet_id' => $stockRequest->outlet_id,
                'warehouse_id' => $stockRequest->warehouse_id,
                'status' => $stockRequest->status,
                'variant_chose' => $stockRequest->detailRequestStock->count(),
                'requested_stock_count' => $stockRequest->detailRequestStock->sum('requested_stock'),
                'requested_at' => $stockRequest->created_at,
                'requested_stock' => StockRequestDetailResource::collection($stockRequest->detailRequestStock),
                'warehouse' => [
                    'id' => optional($stockRequest->warehouse)->id,
                    'name' => optional($stockRequest->warehouse)->name,
                    'alamat' => optional($stockRequest->warehouse)->address,
                    'telp' => optional($stockRequest->warehouse)->telp,
                    'image' => optional($stockRequest->warehouse)->image,
                ],
            ];

            return BaseResponse::Ok("Berhasil mengambil detail stock request !", [$data]);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
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
    public function update(StockRequestUpdateRequest $request, string $id)
    {

        $data = $request->validated();

        if(!auth()->user()->warehouse_id) return BaseResponse::Error("Anda tidak terikat dengan gudang!", 400);
        
        $stockRequest = $this->stockRequest->show($id);
        if(!$stockRequest) return BaseResponse::Notfound("Data permintaan stock tidak ditemukan");

        DB::beginTransaction();
        try {
            $newTotal = 0;

            // Update status
            $stockRequest->update([
                'status' => $data['status'],
            ]);

            // Update each detail

            foreach ($data['product_detail'] as $detail) {
                $existingDetail = $this->stockRequestDetail->customQuery([
                    'stock_request_id' => $id,
                    'product_detail_id' => $detail['product_detail_id'],
                ])->first();

                if ($existingDetail && $data["status"] == "approved") {
                    $this->stockRequestDetail->update($existingDetail->id, [
                        'sended_stock' => $detail['sended_stock'],
                        'price' => $detail['price'] ?? $existingDetail->price,
                    ]);

                    $newTotal += ($detail['sended_stock'] * ($detail['price'] ?? $existingDetail->price));

                    $productStock = $this->productStock->customQuery([
                        "warehouse_id" => auth()->user()->warehouse_id,
                        "product_detail_id" => $detail["product_detail_id"]
                    ])
                    ->first();
                    
                    if($productStock) {
                        $productStock->stock -= $detail["sended_stock"];
                        $productStock->save();
                    }

                    // add product to stock outlet
                    $product = $this->productStock->customQuery(["outlet_id" => $stockRequest->outlet_id, "product_detail_id" => $detail['product_detail_id']])->first();
                    
                    if($product) {
                        $product->stock += $request->stock;
                        $product->save();
                    } else {
                        $this->productStock->store([
                            "outlet_id" => $stockRequest->outlet_id,
                            "stock" => $detail['sended_stock'],
                            "product_detail_id" => $detail['product_detail_id']
                        ]);
                    }
                }
            }
            // Update total di stock request
        if ($data["status"] == "approved") {
            $stockRequest->update(['total' => $newTotal]);
        }

            DB::commit();
            return BaseResponse::Ok('Berhasil mengupdate stock request', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockRequest $stockRequest)
    {
        //
    }
}
