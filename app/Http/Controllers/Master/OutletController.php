<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;
use App\Helpers\BaseResponse;
use App\Services\Auth\UserService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Master\OutletService;
use App\Http\Requests\Master\OutletRequest;
use App\Contracts\Repositories\Auth\UserRepository;
use App\Contracts\Repositories\Master\OutletRepository;
use App\Contracts\Repositories\Master\ProductStockRepository;
use App\Helpers\PaginationHelper;
use App\Http\Resources\OutletDetailResource;
use App\Http\Resources\OutletDetailWithTransactionResource;
use App\Http\Resources\OutletResource;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer;

class OutletController extends Controller
{
    private OutletRepository $outlet;
    private UserRepository $user;
    private OutletService $outletService;
    private UserService $userService;
    private ProductStockRepository $productStockRepository;

    public function __construct(OutletRepository $outlet, UserRepository $user, OutletService $outletService, UserService $userService, ProductStockRepository $productStockRepository)
    {
        $this->outlet = $outlet;
        $this->user = $user;
        $this->outletService = $outletService;
        $this->userService = $userService;
        $this->productStockRepository = $productStockRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 8;
        $page = $request->page ?? 1;
        $payload = [
            "is_delete" => 0
        ];

        // check query filter
        if ($request->search) $payload["search"] = $request->search;
        if ($request->is_delete) $payload["is_delete"] = $request->is_delete;
        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        try {
            $paginate = $this->outlet->customPaginate($per_page, $page, $payload);

            $resource = OutletResource::collection($paginate);
            $result = $resource->collection->values();
            $meta = PaginationHelper::meta($paginate);

            return BaseResponse::Paginate('Berhasil mengambil list data outlet!', $result, $meta);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(OutletRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // check has data user or not 
            $user = $data["user_id"]; // menambahkan/mengganti outlet_id yang dimiliki user yang di iputkan
            unset($data["user_id"]);

            // cek apakah ada menginputkan user baru
            $userCreate = [];
            if (isset($data["users"])) {
                $userCreate = $data["users"];
                unset($data["users"]);
            }
            $userLogin = auth()->user();

            $mapOutlet = $this->outletService->dataOutlet($data);
            $result_outlet = $this->outlet->store($mapOutlet);

            if ($user) {
                $result_user = $this->user->customQuery(["user_id" => $user])->get();
                foreach ($result_user as $dataUser) $dataUser->update(["outlet_id" => $result_outlet->id]);
            }

            // cek apakah ada user create dan apakah user create tersebut adalah array
            if ($userCreate && is_array($userCreate) && !empty($userCreate) && count($userCreate) > 0) {
                // jika ada maka tambahkan user tersebut ke database
                foreach ($userCreate as $userData) {
                    $mapping = $this->userService->mappingDataUser($userData);
                    $mapping["outlet_id"] = $result_outlet->id;
                    if ($userLogin && $userLogin->warehouse_id) {
                        $mapping['warehouse_id'] = $userLogin->warehouse_id;
                    }
                    if ($userLogin && $userLogin->store_id) {
                        $mapping['store_id'] = $userLogin->store_id;
                    }
                    $createUser = $this->user->store($mapping);
                    $createUser->syncRoles(['outlet']);
                }
            }

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat outlet', $result_outlet);
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
        $check_outlet = $this->outlet->show($id);
        if (!$check_outlet) return BaseResponse::Notfound("Tidak dapat menemukan data outlet!");

        $page = request()->get('transaction_page') ?? 1;

        $transactions = $this->outlet->getTransactionsByOutlet($id, 5, $page);
        $meta = PaginationHelper::meta($transactions);

        return BaseResponse::Ok("Berhasil mengambil detail outlet!", [
            'outlet' => new OutletDetailResource($check_outlet),
            'transactions' => [
                'data' => OutletDetailWithTransactionResource::collection($transactions),
                'pagination' => $meta,
            ],
        ]);
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
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data outlet!");

        DB::beginTransaction();
        try {
            // check has data user or not 
            $user = $data["user_id"];
            unset($data["user_id"]);

            $mapOutlet = $this->outletService->dataOutletUpdate($data, $check);
            $result_outlet = $this->outlet->update($id, $mapOutlet);

            if ($user) {
                $result_user = $this->user->customQuery(["user_id" => $user])->get();
                foreach ($result_user as $dataUser) $dataUser->update(["outlet_id" => $result_outlet->id]);
            }

            DB::commit();
            return BaseResponse::Ok('Berhasil update data outlet', $result_outlet);
        } catch (\Throwable $th) {
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
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data outlet!");

        DB::beginTransaction();
        try {
            $this->outlet->delete($id);
            $this->user->customQuery(["outlet_id" => $id])->update(["outlet_id" => null]);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listOutlet(Request $request)
    {
        try {
            $payload = [];
            if ($request->has('is_delete')) $payload["is_delete"] = $request->is_delete;

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            $data = $this->outlet->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data outlet", OutletResource::collection($data));
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function latestStocking(Request $request)
    {
        try {
            $outlet_id = auth()->user()->outlet_id ?? null;

            $stocks = $this->productStockRepository->getLastStocks(null, $outlet_id);

            return BaseResponse::Ok('Berhasil mengambil daftar stocking terakhir', $stocks);
        } catch (\Throwable $th) {
            return BaseResponse::Error('Gagal mengambil daftar stocking terakhir', $th->getMessage());
        }
    }
}
