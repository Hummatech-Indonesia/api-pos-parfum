<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Master\DiscountVoucherInterface;
use App\Enums\UploadDiskEnum;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\DiscountVoucherRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DiscountVoucherController extends Controller
{
    private DiscountVoucherInterface $discountVoucher;

    public function __construct(DiscountVoucherInterface $discountVoucher)
    {
        $this->discountVoucher = $discountVoucher;
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
        if($request->search) $payload["search"] = $request->search;
        if($request->is_delete) $payload["is_delete"] = $request->is_delete;
        if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  

        $data = $this->discountVoucher->customPaginate($per_page, $page, $payload)->toArray();

        $result = $data["data"];
        unset($data["data"]);

        return BaseResponse::Paginate('Berhasil mengambil list data product varian!', $result, $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DiscountVoucherRequest $request)
    {
        $validator = $request->validated();

        DB::beginTransaction();
        try {
            $store_id = null;
            if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $validator["store_id"] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  
            $result_product = $this->discountVoucher->store($validator);

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat product varian', $result_product);
        }catch(\Throwable $th){
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $check_product = $this->discountVoucher->show($id);
        if(!$check_product) return BaseResponse::Notfound("Tidak dapat menemukan data product varian!");

        return BaseResponse::Ok("Berhasil mengambil detail product varian!", $check_product);
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

        $check = $this->discountVoucher->checkActive($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data product varian!");

        DB::beginTransaction();
        try {

            $result_product = $this->discountVoucher->update($id, $validator);
    
            DB::commit();
            return BaseResponse::Ok('Berhasil update data product varian', $result_product);
        }catch(\Throwable $th){
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
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data product varian!");

        DB::beginTransaction();
        try {
            $this->discountVoucher->delete($id);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data', null);
        }catch(\Throwable $th){
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listDischountVoucher(Request $request)
    {
        try{
            $payload = [];

            if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  
            $data = $this->discountVoucher->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data product varian", $data);
        }catch(\Throwable $th) {
          return BaseResponse::Error($th->getMessage(), null);  
        }
    }
}
