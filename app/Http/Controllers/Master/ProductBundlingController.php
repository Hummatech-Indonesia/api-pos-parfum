<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Master\ProductBundlingInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Interfaces\Master\ProductBundlingDetailInterface;
use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ProductBundlingRequest;
use App\Http\Requests\Master\ProductBundlingUpdateRequest;
use App\Http\Resources\ProductBundlingDetailResource;
use App\Http\Resources\ProductBundlingResource;
use App\Models\Unit;
use App\Services\Master\ProductBundlingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBundlingController extends Controller
{
    private $repository, $service;
    private $productRepo, $bundlingDetailRepo, $productDetailRepo, $productStock;

    public function __construct(
        ProductBundlingInterface $repository,
        ProductBundlingService $service,
        ProductInterface $productRepo,
        ProductBundlingDetailInterface $bundlingDetailRepo,
        ProductDetailInterface $productDetailRepo,
        ProductStockInterface $productStock
    ) {
        $this->repository = $repository;
        $this->service = $service;
        $this->productRepo = $productRepo;
        $this->bundlingDetailRepo = $bundlingDetailRepo;
        $this->productDetailRepo = $productDetailRepo;
        $this->productStock = $productStock;
    }

    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $page = $request->page ?? 1;
            $payload = $request->only([
                'search', 'name', 'category', 'product', 'mulai_tanggal', 'sampai_tanggal',
                'min_stock', 'max_stock', 'min_price', 'max_price', 'min_material', 'max_material', 'status'
            ]);
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

            // Simpan ke tabel products
            $productData = $this->service->mapProductData($validated);
            $product = $this->productRepo->store($productData);

            // Simpan ke product_details (tanpa stok)
            $productDetail = $this->productDetailRepo->store([
                'id' => uuid_create(),
                'product_id' => $product->id,
                'product_code' => $validated['kode_Blend'] ?? $this->service->generateBundlingCode($validated['name']),
                'stock' => 0, // stok tidak diatur
                'unit' => 'pcs',
                'price' => $validated['harga'],
                'product_image' => $product->image,
                'is_delete' => 0
            ]);

            // Simpan ke product_bundlings (stok null)
            $bundlingData = $this->service->mapBundlingData($validated, $product->id, $validated['category_id']);
            $bundling = $this->repository->store($bundlingData);

            // Mapping & simpan ke product_bundling_details
            $details = collect($validated['details'][0]['product_bundling_material'])
                ->map(function ($item) {
                    $unit = Unit::find($item['unit_id']);
                    if (!$unit) {
                        throw new \Exception("Unit tidak ditemukan");
                    }

                    return [
                        'product_detail_id' => $item['product_detail_id'],
                        'unit' => $unit->name,
                        'unit_id' => $item['unit_id'],
                        'quantity' => $item['quantity'],
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
            $bundling->load('details.productDetail', 'details.unitRelation');
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

            $validated = $request->validated();

            $bundlingData = [
                'name' => $validated['name'] ?? $bundling->name,
                'category_id' => $validated['category_id'] ?? $bundling->category_id,
                'stock' => null,
                'price' => $validated['harga'] ?? $bundling->price,
                'bundling_code' => $validated['kode_Blend'] ?? $bundling->bundling_code,
            ];
            $this->repository->update($bundling->id, $bundlingData);

            foreach ($validated['details'] as $inputDetail) {
                $unitName = null;
                if (!empty($inputDetail['unit_id'])) {
                    $unit = Unit::find($inputDetail['unit_id']);
                    if (!$unit) {
                        return BaseResponse::Error("Unit tidak ditemukan", 400);
                    }
                    $unitName = $unit->name;
                }

                $existingDetail = $bundling->details
                    ->where('product_detail_id', $inputDetail['product_detail_id'])
                    ->first();

                if ($existingDetail) {
                    $this->bundlingDetailRepo->update($existingDetail->id, [
                        'unit' => $unitName ?? $existingDetail->unit,
                        'unit_id' => $inputDetail['unit_id'] ?? $existingDetail->unit_id,
                        'quantity' => $inputDetail['quantity'] ?? $existingDetail->quantity,
                    ]);
                } else {
                    $this->bundlingDetailRepo->store([
                        'product_bundling_id' => $bundling->id,
                        'product_detail_id' => $inputDetail['product_detail_id'],
                        'unit' => $unitName,
                        'unit_id' => $inputDetail['unit_id'],
                        'quantity' => $inputDetail['quantity'],
                    ]);
                }
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
