<?php

namespace App\Http\Controllers\Dashboard;

use App\Contracts\Interfaces\CategoryInterface;
use App\Enums\UploadDiskEnum;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    private CategoryService $categoryService;
    private CategoryInterface $category;

    public function __construct(CategoryService $categoryService, CategoryInterface $category)
    {
        $this->categoryService = $categoryService;
        $this->category = $category;
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

        $data = $this->category->customPaginate($per_page, $page, $payload)->toArray();

        $result = $data["data"];
        unset($data["data"]);

        return BaseResponse::Paginate('Berhasil mengambil list data category!', $result, $data);
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
        $validator = Validator::make($request->all(), [
            "name" => "required|unique:categories,name",
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
            $result_category = $this->category->store(["name" => $request->name, "store_id" => $store_id]);
    
            DB::commit();
            return BaseResponse::Ok('Berhasil membuat category', $result_category);
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
        $check_category = $this->category->show($id);
        if(!$check_category) return BaseResponse::Notfound("Tidak dapat menemukan data category!");

        $check_category->role = $check_category->getRoleNames();

        return BaseResponse::Ok("Berhasil mengambil detail category!", $check_category);
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
        $validator = Validator::make($request->all(), [
            "name" => "required|unique:categories,name," . $id,
        ],[
            'name.required' => 'Nama kategori harus diisi!',
            'name.unique' => 'Nama kategori telah digunakan!'
        ]);
        
        if ($validator->fails()) {
            return BaseResponse::error("Kesalahan dalam input data!", $validator->errors());
        }

        $check = $this->category->checkActive($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data category!");

        DB::beginTransaction();
        try {

            $result_category = $this->category->update($id, ["name" => $request->name]);
    
            DB::commit();
            return BaseResponse::Ok('Berhasil update data category', $result_category);
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
        
        $check = $this->category->checkActive($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data category!");

        DB::beginTransaction();
        try {
            $this->category->delete($id);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data', null);
        }catch(\Throwable $th){
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listCategory(Request $request)
    {
        try{
            $payload = [];

            if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  
            $data = $this->category->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data category", $data);
        }catch(\Throwable $th) {
          return BaseResponse::Error($th->getMessage(), null);  
        }
    }
}
