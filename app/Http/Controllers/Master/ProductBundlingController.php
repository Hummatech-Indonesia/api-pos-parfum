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
use App\Http\Resources\ProductBundlingDetailResource;
use App\Http\Resources\ProductBundlingResource;
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

            $data = $this->repository->customPaginate($perPage, $page, $payload);

            $bundlings = ProductBundlingResource::collection($data->getCollection());
            $pagination = $data->toArray();

            unset($pagination['data']);

            return BaseResponse::Paginate("Berhasil mengambil data bundling", $bundlings, $pagination);
        } catch (\Throwable $e) {
            return BaseResponse::Error($e->getMessage(), null);
        }
    }

    public function store(ProductBundlingRequest $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            $productData = $this->service->mapProductData($validated);
            $product = $this->productRepo->store($productData);

            $productDetail = $this->productDetailRepo->store([
                'id' => uuid_create(),
                'product_id' => $product->id,
                'product_code' => $validated['kode_Blend'], 
                'stock' => $validated['quantity'],
                'unit' => 'pcs',
                'price' => $validated['harga'],
                'product_image' => $product->image,
                'is_delete' => 0
            ]);

            $bundlingData = $this->service->mapBundlingData($validated, $product->id, $validated['category_id']);
            $bundling = $this->repository->store($bundlingData);

            $details = collect($validated['details'][0]['product_bundling_material'])
                ->map(function ($item) use ($validated, $productDetail) {
                    return [
                        'product_detail_id' => $item['product_detail_id'] ?? $productDetail->id,
                        'unit' => $item['unit'] ?? 'pcs',
                        'unit_id' => $item['unit_id'] ?? null,
                        'quantity' => $item['quantity'] ?? $validated['quantity'],
                    ];
                })->toArray();


            foreach ($details as $detail) {
                $this->bundlingDetailRepo->store([
                    'product_bundling_id' => $bundling->id,
                    'product_detail_id' => $detail['product_detail_id'],
                    'unit' => $detail['unit'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                ]);
            }

             $productDetail = $this->productDetailRepo->show($detail['product_detail_id']);
                if ($productDetail) {
                    $newStock = max(0, $productDetail->stock - $validated['quantity']); // kurangi sesuai stock bundling
                    $this->productDetailRepo->update($productDetail->id, ['stock' => $newStock]);
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

            $bundling = $this->repository->show($id);
            $bundling->load('details.productDetail');
            return BaseResponse::Ok("Detail bundling ditemukan", new ProductBundlingDetailResource($bundling));

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
