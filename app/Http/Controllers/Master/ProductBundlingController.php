<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Master\ProductBundlingInterface;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ProductBundlingRequest;
use App\Http\Requests\Master\ProductBundlingUpdateRequest;
use App\Services\Master\ProductBundlingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBundlingController extends Controller
{
    private $repository, $service;

    public function __construct(ProductBundlingInterface $repository, ProductBundlingService $service)
    {
        $this->repository = $repository;
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $page = $request->page ?? 1;
            $payload = $request->only(['search', 'name', 'category', 'product', 'mulai_tanggal', 'sampai_tanggal']);
            $payload['created_from'] = $payload['mulai_tanggal'] ?? null;
            $payload['created_to'] = $payload['sampai_tanggal'] ?? null;


            $data = $this->repository->customPaginate($perPage, $page, $payload)->toArray();
            $result = $data["data"];
            unset($data["data"]);

            return BaseResponse::Paginate("Berhasil mengambil data bundling", $result, $data);
        } catch (\Throwable $e) {
            return BaseResponse::Error($e->getMessage(), null);
        }
    }

    public function store(ProductBundlingRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $bundling = $this->service->storeBundling($data);
            DB::commit();
            return BaseResponse::Ok("Berhasil membuat bundling", $bundling);
        } catch (\Throwable $e) {
            DB::rollBack();
            return BaseResponse::Error($e->getMessage(), null);
        }
    }

    public function show(string $id)
    {
        try {
            $bundling = $this->repository->show($id);

            if (!$bundling) {
                return BaseResponse::Notfound("Bundling dengan ID $id tidak ditemukan");
            }

            $bundling->load('details');

            return BaseResponse::Ok("Detail bundling ditemukan", $bundling);
        } catch (\Throwable $e) {
            return BaseResponse::Error("Terjadi kesalahan: " . $e->getMessage(), null);
        }
    }

    public function update(ProductBundlingUpdateRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $bundling = $this->repository->show($id);
            if (!$bundling) return BaseResponse::Notfound("Bundling tidak ditemukan");

            $data = $request->validated();
            $updated = $this->service->updateBundling($bundling, $data);
            DB::commit();
            return BaseResponse::Ok("Berhasil update bundling", $updated);
        } catch (\Throwable $e) {
            DB::rollBack();
            return BaseResponse::Error($e->getMessage(), null);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $bundling = $this->repository->show($id);
            if (!$bundling) return BaseResponse::Notfound("Bundling tidak ditemukan");

            $bundling->load('details');

            $deletedData = $bundling->toArray();

            $this->service->deleteBundling($bundling);

            DB::commit();
            return BaseResponse::Ok("Berhasil hapus bundling", $deletedData);
        } catch (\Throwable $e) {
            DB::rollBack();
            return BaseResponse::Error($e->getMessage(), null);
        }
    }


    public function restore(string $id)
    {
        try {
            $data = $this->service->restoreBundling($id);
            return BaseResponse::Ok("Berhasil restore bundling", $data);
        } catch (\Throwable $e) {
            return BaseResponse::Error($e->getMessage(), null);
        }
    }
}
