<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Auth\UserInterface;
use App\Contracts\Interfaces\Master\OutletInterface;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\OutletRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutletController extends Controller
{
    private OutletInterface $outlet;
    private UserInterface $user;

    public function __construct(OutletInterface $outlet, UserInterface $user)
    {
        $this->outlet = $outlet; 
        $this->user = $user; 
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

        try{
            $data = $this->outlet->customPaginate($per_page, $page, $payload)->toArray();
            
            $result = $data["data"];
            unset($data["data"]);
    
            return BaseResponse::Paginate('Berhasil mengambil list data outlet!', $result, $data);
        }catch(\Throwable $th){
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
    public function store(OutletRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // check has data user or not 
            $user = $data["user_id"];
            unset($data["user_id"]);

            $data["store_id"] = auth()?->user()?->store?->id;
            $result_outlet = $this->outlet->store($data);

            if($user){
                $result_user = $this->user->customQuery(["user_id" => $user])->get();
                foreach($result_user as $dataUser) $dataUser->update(["outlet_id" => $result_outlet->id]);
            }
    
            DB::commit();
            return BaseResponse::Ok('Berhasil membuat outlet', $result_outlet);
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
        $check_outlet = $this->outlet->show($id);
        if(!$check_outlet) return BaseResponse::Notfound("Tidak dapat menemukan data outlet!");

        $check_outlet->role = $check_outlet->getRoleNames();

        return BaseResponse::Ok("Berhasil mengambil detail outlet!", $check_outlet);
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
    public function update(OutletRequest $request, string $id)
    {
        $data = $request->validated();

        $check = $this->outlet->checkActive($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data outlet!");

        DB::beginTransaction();
        try {
            // check has data user or not 
            $user = $data["user_id"];
            unset($data["user_id"]);

            $result_outlet = $this->outlet->store($data);

            if($user){
                $result_user = $this->user->customQuery(["user_id" => $user])->get();
                foreach($result_user as $dataUser) $dataUser->update(["outlet_id" => $result_outlet->id]);
            }
    
            DB::commit();
            return BaseResponse::Ok('Berhasil update data outlet', $result_outlet);
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
        
        $check = $this->outlet->checkActive($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data outlet!");

        DB::beginTransaction();
        try {
            $this->outlet->delete($id);
            $this->user->customQuery(["outlet_id" => $id])->update(["outlet_id" => null]);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data', null);
        }catch(\Throwable $th){
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listOutlet(Request $request)
    {
        try{
            $payload = [];

            if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  
            $data = $this->outlet->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data outlet", $data);
        }catch(\Throwable $th) {
          return BaseResponse::Error($th->getMessage(), null);  
        }
    }
}
