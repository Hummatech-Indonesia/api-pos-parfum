<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;
use App\Helpers\BaseResponse;
use App\Helpers\PaginationHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\KategoriPengeluaranResource;
use App\Services\Master\KategoriPengeluaranService;
use App\Http\Requests\Master\KategoriPengeluaranRequest;
use App\Contracts\Repositories\Master\KategoriPengeluaranRepository;

class KategoriPengeluaranController extends Controller
{

    private KategoriPengeluaranService $kategoriPengeluaranService;
    private KategoriPengeluaranRepository $kategoriPengeluaran;

    public function __construct(KategoriPengeluaranRepository $kategoriPengeluaran, KategoriPengeluaranService $kategoriPengeluaranService)
    {
        $this->kategoriPengeluaran = $kategoriPengeluaran;
        $this->kategoriPengeluaranService = $kategoriPengeluaranService;
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

        try {
            $paginate = $this->kategoriPengeluaran->customPaginate($per_page, $page, $payload);

            $resource = KategoriPengeluaranResource::collection($paginate);
            $result = $resource->collection->values();
            $meta = PaginationHelper::meta($paginate);

            return BaseResponse::Paginate('Berhasil mengambil list data kategori pengeluaran!', $result, $meta);
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
    public function store(KategoriPengeluaranRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $mapKategoriPengeluaran = $this->kategoriPengeluaranService->dataKategoriPengeluaran($data);
            $result_kategori_pengeluaran = $this->kategoriPengeluaran->store($mapKategoriPengeluaran);

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat kategori pengeluaran', $result_kategori_pengeluaran);
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
        $check = $this->kategoriPengeluaran->checkActive($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data pengeluaran!");

        $check_kategori_pengeluaran = $this->kategoriPengeluaran->show($id);
        if (!$check_kategori_pengeluaran) return BaseResponse::Notfound("Tidak dapat menemukan data kategori pengeluaran!");

        return BaseResponse::Ok("Berhasil mengambil detail kategori pengeluaran!", new KategoriPengeluaranResource($check_kategori_pengeluaran));
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
    public function update(KategoriPengeluaranRequest $request, string $id)
    {
        $data = $request->validated();

        $check = $this->kategoriPengeluaran->checkActive($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data outlet!");

        DB::beginTransaction();
        try {
            $mapKategoriPengeluaran = $this->kategoriPengeluaranService->dataKategoriPengeluaranUpdate($data, $check);
            $result_kategori_pengeluaran = $this->kategoriPengeluaran->update($id, $mapKategoriPengeluaran);

            DB::commit();
            return BaseResponse::Ok('Berhasil update data kategori pengeluaran', $result_kategori_pengeluaran);
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
        $check = $this->kategoriPengeluaran->checkActive($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data kategori pengeluaran!");

        DB::beginTransaction();
        try {
            $this->kategoriPengeluaran->delete($id);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data kategori pengeluaran', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listKategoriPengeluaran(Request $request)
    {
        try {
            $payload = [];
            if ($request->has('is_delete')) $payload["is_delete"] = $request->is_delete;

            $data = $this->kategoriPengeluaran->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data kategori pengeluaran", KategoriPengeluaranResource::collection($data));
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
