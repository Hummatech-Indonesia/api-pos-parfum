<?php

namespace App\Http\Controllers\Uma;

use App\Contracts\Interfaces\Auth\UserInterface;
use App\Helpers\BaseResponse;
use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\UserSyncRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserDetailResource;
use App\Http\Resources\UserResource;
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
        $request->merge([]);
        $payload = [];

        if ($request->search) $payload["search"] = $request->search;
        if ($request->start_date) $payload["start_date"] = $request->start_date;
        if ($request->end_date) $payload["end_date"] = $request->end_date;

        if (!$request->role && $request->role == "") {
            $request->merge([
                "role" => ['manager', 'auditor', 'warehouse', 'outlet', 'cashier'],
            ]);
        }

        // check if have store_id
        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $request->merge(['store_id' => auth()?->user()?->store?->id ?? auth()?->user()?->store_id]);
        $role_user_outlet = ["owner", "cashier", "employee"];
        $role_user_warehouse = ["warehouse", "cashier", "employee"];
        $role = auth()->user()->roles()->pluck("name")->first();
        if (auth()?->user()?->outlet_id && in_array($role, $role_user_outlet)) {
            $request->merge(['outlet_id' => auth()->user()->outlet_id]);
        }

        if (auth()?->user()?->warehouse_id && in_array($role, $role_user_warehouse)) {
            $request->merge(['warehouse_id' => auth()->user()->warehouse_id]);
        }

        try {
            $paginate = $this->user->customPaginate($per_page, $page, $request->all());
            $resource = UserResource::collection($paginate);
            $result = $resource->collection->values();
            $meta = PaginationHelper::meta($paginate);

            return BaseResponse::Paginate('Berhasil mengambil list data user!', $result, $meta);
        } catch (\Throwable $th) {
            return BaseResponse::Error("Gagal dalam mengambil list paginate user!", $th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $userData = $this->userService->prepareUserCreationData($data, $request->role);
            $result_user = $this->user->store($userData);
            $result_user->syncRoles($request->role);
            DB::commit();
            return BaseResponse::Ok('Berhasil membuat user', $result_user);
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
        $check_user = $this->user->show($id);
        if (!$check_user) return BaseResponse::Notfound("Tidak dapat menemukan data user!");

        $check_user->role = $check_user->getRoleNames();

        return BaseResponse::Ok("Berhasil mengambil detail user!", new UserDetailResource($check_user));
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
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data user!");

        DB::beginTransaction();
        try {
            $user = $this->userService->mappingDataUser($data);
            $this->user->update($id, $user);

            $selectUser = $this->user->show($id);
            $selectUser->syncRoles($request->role);

            DB::commit();
            return BaseResponse::Ok('Berhasil update user', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error('Gagal Update User', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $check = $this->user->show($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data user!");

        try {
            $this->user->delete($id);
            return BaseResponse::Ok('Berhasil menghapus data', null);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listUser(Request $request)
    {
        try {

            $user = $this->user->customQuery($request->all())->get();

            return BaseResponse::Ok("Behasil mengambil data user!", $user);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listUserV2(Request $request)
    {
        try {
            $payload = $request->all();
            $payload["is_delete"] = 0;

            $user = $this->user->customQueryV2($payload)->get();

            return BaseResponse::Ok("Behasil mengambil data user!", $user);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listRole(Request $request)
    {
        try {

            $role = $this->userService->mapRole();

            return BaseResponse::Ok("Behasil mengambil data role!", $role);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function syncStoreData(UserSyncRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            foreach ($data["users"] as $user) {
                try {
                    $user["email"] = str_replace(" ", "", explode(" ", $user["name"])[0]) . date("ymdhms") . "@gmail.com";
                    $user["password"] = bcrypt("password");
                    $user["store_id"] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
                    $result_user = $this->user->store($user);

                    $result_user->syncRoles(["member"]);
                } catch (\Throwable $th) {
                    return BaseResponse::Error($th->getMessage(), null);
                }
            }
            DB::commit();
            return BaseResponse::Ok("Berhasil sikronisasi member!", null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
