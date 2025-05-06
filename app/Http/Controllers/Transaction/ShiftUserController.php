<?php

namespace App\Http\Controllers\Transaction;

use App\Contracts\Interfaces\Transaction\ShiftUserInterface;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\ShiftUserRequest;
use App\Http\Requests\Transaction\ShiftUserSyncRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftUserController extends Controller
{
    private ShiftUserInterface $shiftUser;
    
    public function __construct(ShiftUserInterface $shiftUser)
    {
        $this->shiftUser = $shiftUser;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        $payload = [

        ];

        // check query filter
        if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  

        try {
            $data = $this->shiftUser->customPaginate($per_page, $page, $payload)->toArray();
    
            $result = $data["data"];
            unset($data["data"]);
    
            return BaseResponse::Paginate('Berhasil mengambil list data shift!', $result, $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
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
    public function store(ShiftUserRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // check has data user or not 
            $data["user_id"] = auth()->user()->id;
            $data["store_id"] = auth()->user()?->store?->id ?? auth()->user()?->store_id;
            $result = $this->shiftUser->store($data);

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat warehouse', $result);
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
        $check = $this->shiftUser->show($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data warehouse!");
        
        return BaseResponse::Ok("Berhasil mengambil detail warehouse!", $check);
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
    public function update(ShiftUserRequest $request, string $id)
    {
        $data = $request->validated();

        $check = $this->shiftUser->show($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data warehouse!");

        DB::beginTransaction();
        try {
            $data["user_id"] = auth()->user()->id;
            $result = $check->update($data);
    
            DB::commit();
            return BaseResponse::Ok('Berhasil update data warehouse', $result);
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

    }

     /**
     * Display a listing of the resource.
     */
    public function getData(Request $request)
    {
        $payload = [

        ];

        // check query filter
        if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  

        try {
            $data = $this->shiftUser->customQuery($payload)->get();
    
            return BaseResponse::Ok("Berhasil mengambil data shift", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

      /**
     * Store a newly created resource in storage.
     */
    public function syncStoreData(ShiftUserSyncRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // check has data user or not
            foreach($data["shift"] as $item) {
                $item["store_id"] = auth()->user()?->store?->id ?? auth()->user()?->store_id;
                $this->shiftUser->store($item);
            } 

            DB::commit();
            return BaseResponse::Ok('Berhasil sinkronisasi data shift', null);
        }catch(\Throwable $th){
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
