<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Master\RoleInterface;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Master\RoleRequest;
use App\Models\Role;
use App\Models\User;

class RoleController extends Controller
{
    private RoleInterface $role;

    public function __construct(RoleInterface $role)
    {
        $this->role = $role;
    }

    /**
     * Display a listing of the roles.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page');
            $query = Role::withTrashed()->withCount('users');

            $roles = $perPage ? $query->paginate($perPage) : $query->get();
            return BaseResponse::Ok('Berhasil mengambil list role!', $roles);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }


    /**
     * Store a newly created role.
     */
    public function store(RoleRequest $request)
    {
        DB::beginTransaction();
        try {
            $role = $this->role->store($request->only(['name', 'guard_name']));
            DB::commit();
            return BaseResponse::Ok("Role berhasil dibuat!", $role);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
    /**
     * Display the specified role.
     */
    public function show(string $id)
    {
        $role = $this->role->show($id);
        if (!$role) return BaseResponse::Notfound("Role tidak ditemukan!");

        // Mapping last login untuk setiap user
        $role->users->map(function ($user) {
            $user->last_login = optional($user->tokens->first())->created_at;
            unset($user->tokens); // hapus properti tokens biar tidak panjang
            return $user;
        });

        return BaseResponse::Ok("Detail role berhasil diambil!", $role);
    }


    /**
     * Update the specified role.
     */
    public function update(RoleRequest $request, string $id)
    {
        $check = $this->role->show($id);
        if (!$check) return BaseResponse::Notfound("Role tidak ditemukan!");

        DB::beginTransaction();
        try {
            $updated = $this->role->update($id, $request->only(['name', 'guard_name']));
            DB::commit();
            return BaseResponse::Ok("Role berhasil diupdate!", $updated);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
    /**
     * Remove the specified role.
     */
    public function destroy(string $id)
    {
        $role = $this->role->show($id);
        if (!$role) return BaseResponse::Notfound("Role tidak ditemukan!");

        DB::beginTransaction();
        try {
            $this->role->delete($id);
            DB::commit();
            return BaseResponse::Ok("Role berhasil dihapus!", $role);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function restore(string $id)
    {
        try {
            $restored = $this->role->restore($id);
            if (!$restored) return BaseResponse::Notfound("Role tidak ditemukan atau tidak perlu direstore!");

            return BaseResponse::Ok("Role berhasil direstore!", $restored);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

        public function detachUser(string $roleId, string $userId)
    {
        try {
            $role = Role::find($roleId);
            if (!$role) return BaseResponse::Notfound("Role tidak ditemukan!");

            $user = User::find($userId);
            if (!$user) return BaseResponse::Notfound("User tidak ditemukan!");

            // Menghapus relasi user-role
            $user->roles()->detach($roleId);

            return BaseResponse::Ok("Role berhasil dihapus dari user!", null);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
