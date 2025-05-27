<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\AuditRepository;
use App\Helpers\BaseResponse;
use App\Http\Requests\AuditRequest;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Ausit;

class AuditController extends Controller
{
    private $auditRepository, $service;
    public function __construct(AuditRepository $auditRepository, AuditService $service)
    {
        $this->auditRepository = $auditRepository;
        $this->service = $service;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 8;
        $page = $request->page ?? 1;
        $payload = $request->only(['search', 'name', 'status', 'date']);

        $data['user_id'] = auth()?->user()?->id;

        // check query filter
        if ($request->search) $payload["search"] = $request->search;

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        try {
            $data =  $this->auditRepository->customPaginate($per_page, $page, $payload)->toArray();

            $result = $data["data"];
            unset($data["data"]);
            return BaseResponse::Paginate("Berhasil mengambil semua audit", $result, $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), data: null);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AuditRequest $request)
    {
        $data = $request->validated();

        $settingData['user_id'] = auth()?->user()?->id;

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $data['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        DB::beginTransaction();

        try {

            $settingData = $this->service->storeAudit($data);

            DB::commit();

            return BaseResponse::Ok('Berhasil menambahkan audit', $settingData);
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
        try {
            $audit = $this->auditRepository->show($id);

            if (!$audit) {
                return BaseResponse::Notfound("audit dengan ID $id tidak ditemukan");
            }

            return BaseResponse::Ok("Berhasil mengambil detail setting", $audit);
        } catch (\Throwable $th) {
            return BaseResponse::Error("Terjadi kesalahan: " . $th->getMessage(), null);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AuditRequest $request, string $id)
    {
        $audit = $this->auditRepository->show($id,);
        if (!$audit) return BaseResponse::Notfound("audit tidak ditemukan");

        $settingData = $request->validated();

        $data['user_id'] = auth()?->user()?->id;

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $settingData['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;


        DB::beginTransaction();
        try {

            $update = $this->service->updateAudit($audit, $settingData);

            DB::commit();
            return BaseResponse::Ok('Berhasil memperbarui Audit', $update);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function getData(Request $request)
    {

        $payload = [];

        // check query filter
        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        try {
            $data = $this->auditRepository->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data audit", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $audit = $this->auditRepository->show($id,);
        if (!$audit) return BaseResponse::Notfound("audit tidak ditemukan");
        DB::beginTransaction();

        try {

            $this->service->deleteAudit($audit);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus audit', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function list(Request $request)
    {
        try {
            $payload = [];
            $data = $this->auditRepository->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data audit", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function trashed(Request $request)
    {
        try {
            $payload = [];

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

            $data = $this->auditRepository->allDataTrashed($payload);

            return BaseResponse::Ok("Berhasil mengambil data audit", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function restore(string $id)
    {
        try {
            $audit = $this->auditRepository->restore($id);
            return BaseResponse::Ok("Audit berhasil dikembalikan", $audit);
        } catch (\Throwable $th) {
            return BaseResponse::Error("Gagal mengembalikan audit: " . $th->getMessage(), null);
        }
    }
}
