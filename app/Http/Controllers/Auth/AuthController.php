<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Interfaces\Auth\StoreInterface;
use App\Contracts\Interfaces\Auth\UserInterface;
use App\Contracts\Interfaces\CategoryInterface;
use App\Contracts\Interfaces\Master\DiscountVoucherInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Interfaces\Master\WarehouseInterface;
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
    /**
     * @OA\Info(
     *      version="1.0.0",
     *      title="Laravel 11 API Documentation",
     *      description="Swagger documentation for Laravel 11"
     * )
     */

    private UserInterface $user;
    private StoreInterface $stores;
    private UserService $userService;
    private ProductInterface $product;
    private CategoryInterface $category;
    private DiscountVoucherInterface $discount;
    private WarehouseInterface $warehouse;

    public function __construct(UserInterface $user, StoreInterface $stores, UserService $userService,
    ProductInterface $product, CategoryInterface $category, DiscountVoucherInterface $discount, WarehouseInterface $warehouse)
    {
        $this->user = $user;
        $this->stores = $stores;
        $this->userService = $userService;
        $this->product = $product;
        $this->category = $category;
        $this->discount = $discount;
        $this->warehouse = $warehouse;
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login user",
     *     tags={"Auth"},
     *     operationId="loginUser",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Berhasil melakukan login"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="token", type="string", example="1|sometoken123"),
     *                 @OA\Property(property="role", type="array", @OA\Items(type="string", example="warehouse"))
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *          response=400,
     *          description="Kesalahan server",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Terjadi kesalahan pada server."),
     *              @OA\Property(property="code", type="integer", example=400),
     *              @OA\Property(property="data", type="string", nullable=true, example=null)
     *          )
     *      ),
     *     @OA\Response(
     *         response=401,
     *         description="Akun tidak diizinkan login atau tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Akun anda tidak mendapatkan akses buat login!"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tidak dapat menemukan user!"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *      @OA\Response(
     *          response=500,
     *          description="Kesalahan server",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Terjadi kesalahan pada server."),
     *              @OA\Property(property="code", type="integer", example=500),
     *              @OA\Property(property="data", type="string", nullable=true, example=null)
     *          )
     *      )
     * )
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        try{

            if (auth()->attempt($credentials)) {
    
                $userRoles = auth()->user()?->roles?->pluck('name')->toArray();
                $userAllowLogin = [
                    'warehouse',
                    'outlet',
                    'cashier',
                    'auditor',
                    'owner'
                ];
                if(!array_intersect($userRoles, $userAllowLogin)) return BaseResponse::Custom(false, 'Akun anda tidak mendapatkan akses buat login!, silahkan hubungi admin!', null, 401);
    
                $token = auth()->user()->createToken('authToken')->plainTextToken;
                $user = $this->user->checkUserActive(auth()->user()->id);
    
                if(!$user) return BaseResponse::Notfound("Tidak dapat menukan user!");
    
                $user->role = auth()->user()->roles;
                $user->token = $token;
    
                return BaseResponse::Ok("Berhasil melakukan login", $user);
            }
    
            return BaseResponse::Custom(false, "Akun tidak dapat ditemukan!, Silahkan masukan kembali", null, 401);
        }catch(\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }

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
            $newStore = $this->stores->store($store);
            $warehouse = $this->warehouse->store([
                'store_id' => $newStore->id,
                'name' => $newStore->name,
                'address' => $newStore->address,
            ]);

            $this->user->update($result_user->id, ['warehouse_id'=>$warehouse->id, 'store_id'=>$newStore->id]);

            $result_user->syncRoles(['owner', 'warehouse']);

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

        $user->product_count = $this->product->customQuery(["store_id" => $user?->store_id ?? $user?->store?->id, "is_delete" => 0])->count();
        $user->category_count = $this->category->customQuery(["store_id" => $user?->store_id ?? $user?->store?->id, "is_delete" => 0])->count();
        $user->discount_count = $this->discount->customQuery(["store_id" => $user?->store_id ?? $user?->store?->id, "is_delete" => 0])->count();
        $user->role = auth()->user()->roles->pluck("name");
        $user->token = request()->bearerToken();

        return BaseResponse::Ok('Berhasil mengambil data diri', $user);
    }
}
