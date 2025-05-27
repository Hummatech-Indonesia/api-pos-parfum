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
            $payload = $request->only(['search', 'name', 'category_id', 'product_id', 'created_from', 'created_to']);

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
            $data = $this->repository->show($id)->load('details');
            return BaseResponse::Ok("Detail bundling ditemukan", $data);
        } catch (\Throwable $e) {
            return BaseResponse::Error($e->getMessage(), null);
        }
    }

    public function update(ProductBundlingUpdateRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $bundling = $this->repository->show($id);
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
            $this->service->deleteBundling($bundling);
            DB::commit();
            return BaseResponse::Ok("Berhasil hapus bundling", null);
        } catch (\Throwable $e) {
            DB::rollBack();
            return BaseResponse::Error($e->getMessage(), null);
        }
    }

    public function restore(string $id)
    {
        try {
            $data = $this->repository->restore($id);
            return BaseResponse::Ok("Berhasil restore bundling", $data);
        } catch (\Throwable $e) {
            return BaseResponse::Error($e->getMessage(), null);
        }
    }
}
