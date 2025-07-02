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

            $this->productStock->store([
                'warehouse_id' => auth()->user()->warehouse_id ?? null,
                'outlet_id' => auth()->user()->outlet_id ?? null,
                'product_id' => $product->id,
                'product_detail_id' => $productDetail->id,
                'stock' => $validated['quantity'],
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
                $productStock = $this->productStock->getFromProductDetail($detail['product_detail_id']);

                    if (!$productStock) {
                        DB::rollBack();
                        return BaseResponse::Error("Stock untuk Product Detail ID {$detail['product_detail_id']} tidak ditemukan.", null);
                    }

                    if ($productStock->stock < $detail['quantity']) {
                        DB::rollBack();
                        $productName = $productStock->product?->name ?? 'Tidak diketahui';
                        return BaseResponse::Error("Stok produk '{$productName}' tidak cukup. Sisa: {$productStock->stock}, Dibutuhkan: {$detail['quantity']}", null);
                    }
            }

            foreach ($details as $detail) {
                $this->bundlingDetailRepo->store([
                    'product_bundling_id' => $bundling->id,
                    'product_detail_id' => $detail['product_detail_id'],
                    'unit' => $detail['unit'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                ]);

                $productStock = $this->productStock->getFromProductDetail($detail['product_detail_id']);

                if (!$productStock || $productStock->stock < $detail['quantity']) {
                    DB::rollBack();
                    return BaseResponse::Error("Stok tidak cukup untuk detail ID: {$detail['product_detail_id']}", null);
                }

                $newStock = max(0, $productStock->stock - $detail['quantity']);
                $this->productStock->update($productStock->id, ['stock' => $newStock]);

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

            $validated = $request->validated();

            $bundlingData = [
                'name' => $validated['name'] ?? $bundling->name,
                'category_id' => $validated['category_id'] ?? $bundling->category_id,
                'stock' => $validated['quantity'] ?? $bundling->stock,
                'price' => $validated['harga'] ?? $bundling->price,
                'bundling_code' => $validated['kode_Blend'] ?? $bundling->bundling_code,
            ];
            $this->repository->update($bundling->id, $bundlingData);

            foreach ($validated['details'] as $inputDetail) {
                $existingDetail = $bundling->details
                    ->where('product_detail_id', $inputDetail['product_detail_id'])
                    ->first();

                if ($existingDetail) {
                    $this->bundlingDetailRepo->update($existingDetail->id, [
                        'unit' => $inputDetail['unit'] ?? $existingDetail->unit,
                        'unit_id' => $inputDetail['unit_id'] ?? $existingDetail->unit_id,
                        'quantity' => $inputDetail['quantity'] ?? $existingDetail->quantity,
                    ]);

                } else {
                    $this->bundlingDetailRepo->store([
                        'product_bundling_id' => $bundling->id,
                        'product_detail_id' => $inputDetail['product_detail_id'],
                        'unit' => $inputDetail['unit'],
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
