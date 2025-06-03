<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Master\ProductBundlingInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Interfaces\Master\ProductBundlingDetailInterface;
use App\Contracts\Interfaces\Master\ProductDetailInterface;
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
    private $productRepo, $bundlingDetailRepo, $productDetailRepo;

    public function __construct(
        ProductBundlingInterface $repository,
        ProductBundlingService $service,
        ProductInterface $productRepo,
        ProductBundlingDetailInterface $bundlingDetailRepo,
        ProductDetailInterface $productDetailRepo
    ) {
        $this->repository = $repository;
        $this->service = $service;
        $this->productRepo = $productRepo;
        $this->bundlingDetailRepo = $bundlingDetailRepo;
        $this->productDetailRepo = $productDetailRepo;
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
            $validated = $request->validated();

            $productData = $this->service->mapProductData($validated['product']);
            $bundlingData = $this->service->mapBundlingData($validated);
            $detailsData = $this->service->mapDetailData($validated['details']);

            $product = $this->productRepo->store($productData);

            $bundlingData['product_id'] = $product->id;
            $bundlingData['category_id'] = $product->category_id;
            $bundling = $this->repository->store($bundlingData);

            foreach ($detailsData as $detail) {
                $productDetail = $this->productDetailRepo->store([
                    'id' => uuid_create(),
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    ...$detail['product_detail'],
                ]);

                $this->bundlingDetailRepo->store([
                    'product_bundling_id' => $bundling->id,
                    'product_detail_id' => $productDetail->id,
                    'unit' => $detail['unit'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                ]);
            }

            DB::commit();
            return BaseResponse::Ok("Berhasil membuat bundling", $this->repository->show($bundling->id)->load('details'));
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
            $detailsData = $this->service->mapDetailData($data['details']);

            // Delete existing details
            foreach ($bundling->details as $detail) {
                $this->bundlingDetailRepo->delete($detail->id);
            }

            // Re-create details
            foreach ($detailsData as $detail) {
                $this->bundlingDetailRepo->store([
                    'product_bundling_id' => $bundling->id,
                    'product_detail_id' => $detail['product_detail_id'],
                    'unit' => $detail['unit'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                ]);
            }

            DB::commit();
            return BaseResponse::Ok("Berhasil update bundling", $this->repository->show($bundling->id)->load('details'));
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

            foreach ($bundling->details as $detail) {
                $this->bundlingDetailRepo->delete($detail->id);
            }

            $this->repository->delete($bundling->id);

            DB::commit();
            return BaseResponse::Ok("Berhasil hapus bundling", $deletedData);
        } catch (\Throwable $e) {
            DB::rollBack();
            return BaseResponse::Error($e->getMessage(), null);
        }
    }

    public function restore(string $id)
    {
        DB::beginTransaction();
        try {
            $this->repository->restore($id);

            $bundling = $this->repository->show($id);
            foreach ($bundling->details()->withTrashed()->get() as $detail) {
                $this->bundlingDetailRepo->restore($detail->id);
            }

            DB::commit();
            return BaseResponse::Ok("Berhasil restore bundling", $bundling->load('details'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return BaseResponse::Error($e->getMessage(), null);
        }
    }
}
