<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;
use App\Helpers\BaseResponse;
use App\Services\Auth\UserService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Master\WarehouseService;
use App\Http\Requests\WarehouseStockRequest;
use App\Http\Requests\Master\WarehouseRequest;
use App\Contracts\Interfaces\Auth\UserInterface;
use App\Contracts\Interfaces\Master\WarehouseInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\WarehouseStockInterface;
use App\Helpers\PaginationHelper;
use App\Http\Resources\RestockWarehouseCollection;
use App\Http\Resources\RestockWarehouseResource;

class WarehouseController extends Controller
{
    private WarehouseInterface $warehouse;
    private UserInterface $user;
    private WarehouseStockInterface $warehouseStock;
    private ProductDetailInterface $productDetail;
    private ProductStockInterface $productStock;
    private WarehouseService $warehouseService;
    private UserService $userService;

    public function __construct(
        WarehouseInterface $warehouse,
        UserInterface $user,
        WarehouseStockInterface $warehouseStock,
        ProductDetailInterface $productDetail,
        ProductStockInterface $productStock,
        WarehouseService $warehouseService,
        UserService $userService
    ) {
        $this->warehouse = $warehouse;
        $this->user = $user;
        $this->warehouseStock = $warehouseStock;
        $this->productDetail = $productDetail;
        $this->productStock = $productStock;
        $this->warehouseService = $warehouseService;
        $this->userService = $userService;
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

        $data = $this->warehouse->customPaginate($per_page, $page, $payload)->toArray();

        $result = collect($data["data"])->map(function ($warehouse) {
            $warehouseModel = $this->warehouse->withProductStocks($warehouse['id']);
            $warehouse['product_count'] = $warehouseModel->productStocks->count();
            unset($warehouse['product_stock']);
            return $warehouse;
        });
        unset($data["data"]);

        return BaseResponse::Paginate('Berhasil mengambil list data warehouse!', $result, $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

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

            // cek apakah ada menginputkan user baru
            $userCreate = [];
            if (isset($data["users"])) {
                $userCreate = $data["users"];
                unset($data["users"]);
            }
            $userLogin = auth()->user();

            $mapWarehouse = $this->warehouseService->dataWarehouse($data);
            $result_warehouse = $this->warehouse->store($mapWarehouse);

            if ($user) {
                $result_user = $this->user->customQuery(["user_id" => $user])->get();
                foreach ($result_user as $dataUser) $dataUser->update(["warehouse_id" => $result_warehouse->id]);
            }

            // cek apakah ada user dan apakah user create tersebut adalah array
            if ($userCreate && is_array($userCreate) && !empty($userCreate) && count($userCreate) > 0) {
                // jika ada maka tambahkan user tersebut ke database
                foreach ($userCreate as $userData) {
                    $mapping = $this->userService->mappingDataUser($userData);
                    $mapping["warehouse_id"] = $result_warehouse->id;
                    if ($userLogin && $userLogin->outlet_id) {
                        $mapping['outlet_id'] = $userLogin->outlet_id;
                    }
                    if ($userLogin && $userLogin->store_id) {
                        $mapping['store_id'] = $userLogin->store_id;
                    }
                    $createUser = $this->user->store($mapping);
                    $createUser->syncRoles(['warehouse']);
                }
            }

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat warehouse', $result_warehouse);
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
        $check_warehouse = $this->warehouse->show($id);
        if (!$check_warehouse) {
            return BaseResponse::Notfound("Tidak dapat menemukan data warehouse!");
        }

        $per_page = $request->per_page ?? 5;
        $page = $request->page ?? 1;

        $productStocks = $this->warehouse->getProductStocksPaginated($id, $per_page, $page);

        return BaseResponse::Ok("Berhasil mengambil detail warehouse!", [
            "warehouse" => $check_warehouse,
            "product_count" => $productStocks->total(),
            "product_stocks" => $productStocks
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
    public function update(WarehouseRequest $request, string $id)
    {
        $data = $request->validated();

        $check = $this->warehouse->checkActive($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data warehouse!");

        DB::beginTransaction();
        try {
            // check has data user or not 
            $user = $data["user_id"];
            unset($data["user_id"]);

            $mapWarehouse = $this->warehouseService->dataWarehouseUpdate($data, $check);
            $result_outlet = $this->warehouse->update($id, $mapWarehouse);

            if ($user) {
                $result_user = $this->user->customQuery(["user_id" => $user])->get();
                foreach ($result_user as $dataUser) $dataUser->update(["warehouse_id" => $result_outlet->id]);
            }

            DB::commit();
            return BaseResponse::Ok('Berhasil update data warehouse', $result_outlet);
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

        $check = $this->warehouse->checkActive($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data warehouse!");

        DB::beginTransaction();
        try {
            $this->warehouse->delete($id);
            $this->user->customQuery(["warehouse_id" => $id])->update(["warehouse_id" => null]);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listWarehouse(Request $request)
    {
        try {
            $payload = [];

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            $data = $this->warehouse->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data warehouse", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listWarehouseStock(Request $request)
    {
        $per_page = $request->per_page ?? 8;
        $page = $request->page ?? 1;
        $payload = [];

        if ($request->from_date) $payload["from_date"] = $request->from_date . ' 00:00:00';
        if ($request->until_date) $payload["until_date"] = $request->until_date . ' 23:59:59';
        if ($request->min_stock) $payload["min_stock"] = $request->min_stock;
        if ($request->max_stock) $payload["max_stock"] = $request->max_stock;
        $payload['sort_by'] = $request->sort_by ?? 'updated_at';
        $payload['sort_direction'] = $request->sort_direction ?? 'desc';

        try {
            $result = $this->warehouseStock->customPaginate($per_page, $page, $payload);
            $resource = new RestockWarehouseCollection($result);
            $meta = PaginationHelper::meta($result);

            return BaseResponse::Paginate(
                "Berhasil menampilkan riwayat stock",
                $resource,
                $meta
            );
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function warehouseStock(WarehouseStockRequest $request)
    {
        $data = $request->validated();
        $user = auth()->user();

        DB::beginTransaction();
        try {
            foreach ($data['restock'] as $item) {

                $this->warehouseStock->store([
                    'user_id' => $user->id,
                    'product_detail_id' => $item['variant_id'],
                    'stock' => $item['requested_stock'],
                    'unit_id' => $item['unit_id'],
                    'store_name' => isset($data['store_name']) ? $data['store_name'] : null,
                    'total_price' => isset($data['total_price']) ? $data['total_price'] : null,
                    'store_location' => isset($data['store_location']) ? $data['store_location'] : null,
                    'reason' => '-',
                ]);

                $productStock = $this->productStock->customQuery([
                    'warehouse_id' => $user->warehouse_id,
                    'product_detail_id' => $item['variant_id']
                ])->first();

                if ($productStock) {
                    $productStock->stock += $item['requested_stock'];
                    $productStock->save();
                } else {
                    $this->productStock->store([
                        'warehouse_id' => $user->warehouse_id,
                        'product_detail_id' => $item['variant_id'],
                        'stock' => $item['requested_stock'],
                    ]);
                }
            }

            DB::commit();
            return BaseResponse::Create("Berhasil memproses restock", null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error('Gagal menambah stock', $th->getMessage());
        }
    }

    public function restockByPeriod(Request $request)
    {
        try {
            $date = $request->query('date');

            $data = $this->warehouseStock->getAll($date);

            return BaseResponse::Ok(
                'Berhasil menampilkan restock',
                new RestockWarehouseCollection($data)
            );
        } catch (\Throwable $th) {
            return BaseResponse::Error('Gagal menampilkan data restock', $th->getMessage());
        }
    }

    public function getTotalExpenditure()
    {
        try {
            $total = $this->warehouseStock->getTotalExpenditure();

            return BaseResponse::Ok('Berhasil mengambil total pengeluaran', $total);
        } catch (\Throwable $th) {
            return BaseResponse::Error('Gagal mengambil total pengeluaran', $th->getMessage());
        }
    }

    public function latestStocking(Request $request)
    {
        try {
            $warehouse_id = auth()->user()->warehouse_id ?? null;

            $stocks = $this->productStock->getLastStocks($warehouse_id, null);

            return BaseResponse::Ok('Berhasil mengambil daftar stocking terakhir', $stocks);
        } catch (\Throwable $th) {
            return BaseResponse::Error('Gagal mengambil daftar stocking terakhir', $th->getMessage());
        }
    }

    public function expenditureByDate()
    {
        try {
            $data = $this->warehouseStock->getSpendingByDate();

            return BaseResponse::Ok('Berhasil mengambil data pengeluaran harian', $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error('Gagal mengambil data pengeluaran harian', $th->getMessage());
        }
    }
}
