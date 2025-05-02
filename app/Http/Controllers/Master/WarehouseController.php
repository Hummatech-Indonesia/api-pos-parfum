<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Auth\UserInterface;
use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Interfaces\Master\WarehouseInterface;
use App\Contracts\Interfaces\Master\WarehouseStockInterface;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\WarehouseRequest;
use App\Http\Requests\WarehouseStockRequest;
use App\Services\Master\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    private WarehouseInterface $warehouse;
    private UserInterface $user;
    private WarehouseStockInterface $warehouseStock;
    private ProductDetailInterface $productDetail;
    private ProductStockInterface $productStock;
    private WarehouseService $warehouseService;

    public function __construct(WarehouseInterface $warehouse, UserInterface $user, 
    WarehouseStockInterface $warehouseStock, ProductDetailInterface $productDetail,
    ProductStockInterface $productStock, WarehouseService $warehouseService
    )
    {
        $this->warehouse = $warehouse; 
        $this->user = $user; 
        $this->warehouseStock = $warehouseStock; 
        $this->productDetail = $productDetail; 
        $this->productStock = $productStock; 
        $this->warehouseService = $warehouseService;
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

        $data = $this->warehouse->customPaginate($per_page, $page, $payload)->toArray();

        $result = $data["data"];
        unset($data["data"]);

        return BaseResponse::Paginate('Berhasil mengambil list data warehouse!', $result, $data);
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

            $mapWarehouse = $this->warehouseService->dataWarehouse($data);
            $result_warehouse = $this->warehouse->store($mapWarehouse);

            if($user){
                $result_user = $this->user->customQuery(["user_id" => $user])->get();
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

            $mapWarehouse = $this->warehouseService->dataWarehouseUpdate($data, $check);
            $result_outlet = $this->warehouse->update($id, $mapWarehouse);

            if($user){
                $result_user = $this->user->customQuery(["user_id" => $user])->get();
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

        DB::beginTransaction();
        try {
            $this->warehouse->delete($id);
            $this->user->customQuery(["warehouse_id" => $id])->update(["warehouse_id" => null]);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data', null);
        }catch(\Throwable $th){
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listWarehouse(Request $request)
    {
        try{
            $payload = [];

            if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  
            $data = $this->warehouse->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data warehouse", $data);
        }catch(\Throwable $th) {
          return BaseResponse::Error($th->getMessage(), null);  
        }
    }

    public function listWarehouseStock(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        $payload = [];

        if($request->date) $payload["date"] = $request->date;

        try {
            return BaseResponse::Ok(
                "Berhasil menampilkan riwayat stock", 
                $this->warehouseStock->customPaginate($per_page, $page, $payload)
            );
        }catch(\Throwable $th){
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function warehouseStock(WarehouseStockRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $data["user_id"] = auth()->user()->id;
            $stock = $this->warehouseStock->store($data);
            $product = $this->productStock->customQuery(["warehouse_id" => auth()->user()->warehouse_id, "product_detail_id" => $request->product_detail_id])->first();
            if($product) {
                $product->stock += $request->stock;
                $product->save();
            } else {
                $this->productStock->store([
                    "warehouse_id" => $request->warehouse_id,
                    "stock" => $request->stock,
                    "product_detail_id" => $request->product_detail_id
                ]);
            }
            // $this->productDetail->update($request->product_detail_id, ["stock" => $request->stock]);
            DB::commit();
            return BaseResponse::Ok("Berhasil menambahkan stock warehouse", $stock);
        } catch(\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);  
        }
    }
}
