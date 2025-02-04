<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Master\ProductVarianInterface;
use App\Enums\UploadDiskEnum;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductVarianController extends Controller
{
    private ProductVarianInterface $productVarian;

    public function __construct(ProductVarianInterface $productVarian)
    {
        $this->productVarian = $productVarian;
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

        $data = $this->productVarian->customPaginate($per_page, $page, $payload)->toArray();

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
    public function store(Request $request)
    {
        $store_id = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                Rule::unique('product_varians','name')->where(function ($query) use ($store_id) {
                    return $query->where('store_id', $store_id);
                }),
            ],
        ],[
            'name.required' => 'Nama kategori harus diisi!',
            'name.unique' => 'Nama kategori telah digunakan!'
        ]);
        
        if ($validator->fails()) {
            return BaseResponse::error("Kesalahan dalam input data!", $validator->errors());
        }

        DB::beginTransaction();
        try {
            $store_id = null;
            if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $store_id = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  
            $result_product = $this->productVarian->store(["name" => $request->name, "store_id" => $store_id]);

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
        $check_product = $this->productVarian->show($id);
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
    public function update(Request $request, string $id)
    {
        $store_id = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                Rule::unique('product_varians','name')->where(function ($query) use ($store_id) {
                    return $query->where('store_id', $store_id);
                })->ignore($id),
            ],
        ],[
            'name.required' => 'Nama kategori harus diisi!',
            'name.unique' => 'Nama kategori telah digunakan!'
        ]);
        
        if ($validator->fails()) {
            return BaseResponse::error("Kesalahan dalam input data!", $validator->errors());
        }

        $check = $this->productVarian->checkActive($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data product varian!");

        DB::beginTransaction();
        try {

            $result_product = $this->productVarian->update($id, ["name" => $request->name]);
    
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
        
        $check = $this->productVarian->checkActive($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data product varian!");
        if($check->products_count) return BaseResponse::Notfound("Data masih terikat dalam product!");

        DB::beginTransaction();
        try {
            $this->productVarian->delete($id);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data', null);
        }catch(\Throwable $th){
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listProductVarian(Request $request)
    {
        try{
            $payload = [
                "is_delete" => 0
            ];

            if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  
            $data = $this->productVarian->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data product varian", $data);
        }catch(\Throwable $th) {
          return BaseResponse::Error($th->getMessage(), null);  
        }
    }
}
