<?php

namespace App\Http\Controllers\Uma;

use App\Contracts\Interfaces\Auth\UserInterface;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Services\Auth\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    private UserInterface $user;
    private UserService $userService;

    public function __construct(UserInterface $user, UserService $userService)
    {
        $this->user = $user;
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        $payload = [
            "role" => ['manager','auditor','warehouse','outlet','cashier'],
            "is_delete" => 0
        ];

        // check if have store_id
        if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  
        // check if has request query for check role
        if($request->role) $payload['role'] = $request->role;
        if($request->search) $payload['search'] = $request->search;
        if($request->is_delete) $payload['is_delete'] = $request->is_delete;

        try{
            $result_user = $this->user->customPaginate($per_page, $page, $payload)->toArray();
    
            $data = $result_user["data"];
            unset($result_user["data"]);
    
            return BaseResponse::Paginate('Berhasil mengambil list data user!', $data, $result_user);
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
    public function store(UserRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $user = $this->userService->mappingDataUser($data);
            $user["store_id"] = auth()?->user()?->store?->id;
            $result_user = $this->user->store($user);
    
            $result_user->syncRoles($request->role);
            DB::commit();
            return BaseResponse::Ok('Berhasil membuat user', $result_user);
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
        $check_user = $this->user->show($id);
        if(!$check_user) return BaseResponse::Notfound("Tidak dapat menemukan data user!");

        $check_user->role = $check_user->getRoleNames();

        return BaseResponse::Ok("Berhasil mengambil detail user!", $check_user);
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
    public function update(UserRequest $request, string $id)
    {
        $data = $request->validated();

        $check = $this->user->show($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data user!");

        DB::beginTransaction();
        try {
            $user = $this->userService->mappingDataUser($data);
            $result_user = $this->user->update($id, $user);
    
            $result_user->syncRoles($request->role);

            DB::commit();
            return BaseResponse::Ok('Berhasil update user', null);
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
        
        $check = $this->user->show($id);
        if(!$check) return BaseResponse::Notfound("Tidak dapat menemukan data user!");

        try {
            $this->user->delete($id);
            return BaseResponse::Ok('Berhasil menghapus data', null);
        }catch(\Throwable $th){
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listUser(Request $request)
    {
        try{

            $user = $this->user->customQuery($request->all())->get();
    
            return BaseResponse::Ok("Behasil mengambil data user!", $user);
        }catch(\Throwable $th){
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
