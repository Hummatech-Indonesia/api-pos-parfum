<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Master\DiscountVoucherInterface;
use App\Enums\UploadDiskEnum;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\DiscountVoucherRequest;
use App\Http\Resources\DiscountVoucherResource;
use App\Models\ProductDetail;
use App\Services\DiscountVoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DiscountVoucherController extends Controller
{
    private DiscountVoucherInterface $discountVoucher;
    private DiscountVoucherService $discountVoucherService;

    public function __construct(DiscountVoucherInterface $discountVoucher, DiscountVoucherService $discountVoucherService)
    {
        $this->discountVoucher = $discountVoucher;
        $this->discountVoucherService = $discountVoucherService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        $payload = [
            "is_delete" => 0,
            "sort_by" => $request->sort_by,
            "sort_direction" => $request->sort_direction
        ];


        if ($request->search) $payload["search"] = $request->search;
        if ($request->name) $payload["name"] = $request->name;
        if ($request->active !== null) $payload["active"] = $request->active;
        if ($request->min_discount) $payload["min_discount"] = $request->min_discount;
        if ($request->max_discount) $payload["max_discount"] = $request->max_discount;
        if ($request->start_date) $payload["start_date"] = $request->start_date;
        if ($request->end_date) $payload["end_date"] = $request->end_date;

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        $collection = $this->discountVoucher->customPaginate($per_page, $page, $payload);

        $data = DiscountVoucherResource::collection($collection->items());
        $meta = [
            'total' => $collection->total(),
            'per_page' => $collection->perPage(),
            'current_page' => $collection->currentPage(),
            'last_page' => $collection->lastPage(),
            'from' => $collection->firstItem(),
            'to' => $collection->lastItem(),
        ];

        return BaseResponse::Paginate('Berhasil mengambil list data!', $data, $meta);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(DiscountVoucherRequest $request)
    {
        $validator = $request->validated();
        $validator['expired'] = $request->end_date;
        $validator['min'] = $request->minimum_purchase;
        unset($validator['end_date'], $validator['minimum_purchase']);

        $store_id = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
        $validator["store_id"] = $store_id;



        DB::beginTransaction();
        try {
            if (isset($validator['product_detail_id'])) {
                $isValid = ProductDetail::where('id', $validator['product_detail_id'])
                    ->whereHas('product', function ($query) use ($store_id) {
                        $query->where('store_id', $store_id);
                    })
                    ->exists();

                if (!$isValid) {
                    return BaseResponse::Error("Produk yang dipilih tidak valid untuk store Anda.", null);
                }
            }
            $store_id = null;
            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $validator["store_id"] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;


            $result_product = $this->discountVoucher->store($validator);

            DB::commit();
            $result_product->refresh();
            $result_product->load('details.product');
            return BaseResponse::Create('Berhasil membuat diskon!', new DiscountVoucherResource($result_product));
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
            $check_product = $this->discountVoucher->show($id);
            if (!$check_product) return BaseResponse::Notfound("Tidak dapat menemukan data dengan ID: " . $id);

            return BaseResponse::Ok("Berhasil mengambil detail!", new DiscountVoucherResource($check_product));
        } catch (\Throwable $th) {
            return BaseResponse::Error("Terjadi kesalahan: " . $th->getMessage(), null);
        }
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
    public function update(DiscountVoucherRequest $request, string $id)
    {
        $validator = $request->validated();
        $validator['expired'] = $request->end_date;
        $validator['min'] = $request->minimum_purchase;
        unset($validator['end_date'], $validator['minimum_purchase']);

        $check = $this->discountVoucher->checkActive($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data!");

        DB::beginTransaction();
        try {

            $result_product = $this->discountVoucher->update($id, $validator);

            DB::commit();
            $result_product->load('details.product');
            return BaseResponse::Ok('Diskon Berhasil Diperbarui!', new DiscountVoucherResource($result_product));
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

        $check = $this->discountVoucher->checkActive($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data!");

        DB::beginTransaction();
        try {
            $this->discountVoucher->delete($id);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listDiscountVoucher(Request $request)
    {
        try {
            $payload = [];

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            $data = $this->discountVoucher->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
