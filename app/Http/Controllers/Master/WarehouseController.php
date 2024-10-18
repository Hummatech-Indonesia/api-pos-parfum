<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Auth\UserInterface;
use App\Contracts\Interfaces\Master\WarehouseInterface;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\WarehouseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    private WarehouseInterface $warehouse;
    private UserInterface $user;

    public function __construct(WarehouseInterface $warehouse, UserInterface $user)
    {
        $this->warehouse = $warehouse; 
        $this->user = $user; 
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
        if($request->search) $payload["search"] = $request->search;
        if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  

        $data = $this->warehouse->customPaginate($per_page, $page, $payload);
        return BaseResponse::Ok('Berhasil mengambil list data warehouse!', $data);
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
    public function store(WarehouseRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // check has data user or not 
            $user = $data["user_id"];
            unset($data["user_id"]);

            $data["store_id"] = auth()?->user()?->store?->id;
            $result_warehouse = $this->warehouse->store($data);

            if($user){
                $result_user = $this->user->customQuery($user);
                foreach($result_user as $dataUser) $dataUser->update(["warehouse_id" => $result_warehouse->id]);
            }
    
            DB::commit();
            return BaseResponse::Ok('Berhasil membuat warehouse', $result_warehouse);
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
        $check_warehouse = $this->warehouse->show($id);
        if(!$check_warehouse) return BaseResponse::Notfound("Tidak dapat menemukan data warehouse!");

        $check_warehouse->role = $check_warehouse->getRoleNames();

        return BaseResponse::Ok("Berhasil mengambil detail warehouse!", $check_warehouse);
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
    public function update(WarehouseRequest $request, string $id)
    {
        $data = $request->validated();

        $check = $this->warehouse->checkActive($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data warehouse!");

        DB::beginTransaction();
        try {
            // check has data user or not 
            $user = $data["user_id"];
            unset($data["user_id"]);

            $result_outlet = $this->warehouse->store($user);

            if($user){
                $result_user = $this->user->customQuery($data);
                foreach($result_user as $dataUser) $dataUser->update(["warehouse_id" => $result_outlet->id]);
            }
    
            DB::commit();
            return BaseResponse::Ok('Berhasil update data warehouse', $result_outlet);
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
        
        $check = $this->warehouse->checkActive($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data warehouse!");

        try {
            $this->warehouse->delete($id);
            return BaseResponse::Ok('Berhasil menghapus data', null);
        }catch(\Throwable $th){
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
