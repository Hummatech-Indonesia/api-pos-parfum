<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;
use App\Helpers\BaseResponse;
use App\Helpers\PaginationHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\PengeluaranRequest;
use App\Http\Resources\PengeluaranResource;
use App\Services\Master\PengeluaranService;
use App\Contracts\Repositories\Master\PengeluaranRepository;

class PengeluaranController extends Controller
{
    private PengeluaranService $pengeluaranService;
    private PengeluaranRepository $pengeluaran;

    public function __construct(PengeluaranRepository $pengeluaran, PengeluaranService $pengeluaranService)
    {
        $this->pengeluaran = $pengeluaran;
        $this->pengeluaranService = $pengeluaranService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 8;
        $page = $request->page ?? 1;
        $payload = [];

        // check query filter
        if ($request->search) $payload["search"] = $request->search;
        if ($request->warehouse_id) $payload["warehouse_id"] = $request->warehouse_id;
        if ($request->outlet_id) $payload["outlet_id"] = $request->outlet_id;

        try {
            $paginate = $this->pengeluaran->customPaginate($per_page, $page, $payload);

            $resource = PengeluaranResource::collection($paginate);
            $result = $resource->collection->values();
            $meta = PaginationHelper::meta($paginate);

            return BaseResponse::Paginate('Berhasil mengambil list data pengeluaran!', $result, $meta);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PengeluaranRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $mapPengeluaran = $this->pengeluaranService->dataPengeluaran($data);
            $result_pengeluaran = $this->pengeluaran->store($mapPengeluaran);

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat pengeluaran', $result_pengeluaran);
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

        $check_pengeluaran = $this->pengeluaran->show($id);
        if (!$check_pengeluaran) return BaseResponse::Notfound("Tidak dapat menemukan data pengeluaran!");

        return BaseResponse::Ok("Berhasil mengambil detail pengeluaran!", new PengeluaranResource($check_pengeluaran));
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
    public function update(PengeluaranRequest $request, string $id)
    {
        $data = $request->validated();

        $check = $this->pengeluaran->show($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data pengeluaran!");

        DB::beginTransaction();
        try {
            $mapPengeluaran = $this->pengeluaranService->dataPengeluaranUpdate($data, $check);
            $result_pengeluaran = $this->pengeluaran->update($id, $mapPengeluaran);

            DB::commit();
            return BaseResponse::Ok('Berhasil update data pengeluaran', $result_pengeluaran);
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
        $check = $this->pengeluaran->show($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data pengeluaran!");

        DB::beginTransaction();
        try {
            $this->pengeluaran->delete($id);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data pengeluaran', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listPengeluaran(Request $request)
    {
        try {
            $payload = [];
            if ($request->warehouse_id) $payload["warehouse_id"] = $request->warehouse_id;
            if ($request->outlet_id) $payload["outlet_id"] = $request->outlet_id;

            $data = $this->pengeluaran->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data pengeluaran", PengeluaranResource::collection($data));
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
