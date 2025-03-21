<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Interfaces\Auth\StoreInterface;
use App\Contracts\Interfaces\Auth\UserInterface;
use App\Contracts\Interfaces\CategoryInterface;
use App\Contracts\Interfaces\Master\DiscountVoucherInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\UserRequest;
use App\Services\Auth\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    private UserInterface $user;
    private StoreInterface $stores;
    private UserService $userService;
    private ProductInterface $product;
    private CategoryInterface $category;
    private DiscountVoucherInterface $discount;

    public function __construct(UserInterface $user, StoreInterface $stores, UserService $userService,
    ProductInterface $product, CategoryInterface $category, DiscountVoucherInterface $discount)
    {
        $this->user = $user;
        $this->stores = $stores;
        $this->userService = $userService;
        $this->product = $product;
        $this->category = $category;
        $this->discount = $discount;
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (auth()->attempt($credentials)) {
            $token = auth()->user()->createToken('authToken')->plainTextToken;
            $user = $this->user->checkUserActive(auth()->user()->id);

            if(!$user) return BaseResponse::Notfound("Tidak dapat menukan user!");

            $user->role = auth()->user()->roles;
            $user->token = $token;

            return BaseResponse::Ok("Berhasil melakukan login", $user);
        }

        return BaseResponse::Custom(false, "Akun tidak dapat ditemukan!, Silahkan masukan kembali", null, 401);
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        
        try {

            $user = $this->userService->mappingDataUser($data);
            $result_user = $this->user->store($user);
            
            $data["user_id"] = $result_user->id;
            if($request->hasFile('logo')) $data["logo"] = $request->file('logo');
            $store = $this->userService->addStore($data);
            $this->stores->store($store);

            $result_user->syncRoles(['owner']);

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat akun', null);
        }catch(\Throwable $th){
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return BaseResponse::Ok("Berhasil logout", null);
    }

    public function getMe(){
        if(!auth()->user()){
            return BaseResponse::Error('Token tidak valid!', null);
        }

        $user = $this->user->checkUserActive(auth()->user()->id);
        if(!$user){
            return BaseResponse::Notfound('Data diri tidak ditemukan, silahkan login ulang!');
        }

        $user->product_count = $this->product->customQuery(["store_id" => $user?->store_id])->count();
        $user->category_count = $this->category->customQuery(["store_id" => $user?->store_id])->count();
        $user->discount_count = $this->discount->customQuery(["store_id" => $user?->store_id])->count();
        $user->role = auth()->user()->roles;
        $user->token = request()->bearerToken();

        return BaseResponse::Ok('Berhasil mengambil data diri', $user);
    }
}
