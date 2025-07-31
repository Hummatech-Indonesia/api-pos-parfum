<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\AuditRepository;
use App\Contracts\Repositories\Master\ProductDetailRepository;
use App\Contracts\Repositories\Master\ProductStockRepository;
use App\Helpers\BaseResponse;
use App\Helpers\PaginationHelper;
use App\Http\Requests\AuditRequest;
use App\Http\Resources\AuditResource;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Contracts\Repositories\AuditDetailRepository;

class AuditController extends Controller
{
    private $auditRepository, $service, $productDetail, $auditDetail, $productStock;
    public function __construct(AuditRepository $auditRepository, AuditService $service, ProductDetailRepository $productDetail, AuditDetailRepository $auditDetail, ProductStockRepository $productStock)
    {
        $this->auditRepository = $auditRepository;
        $this->service = $service;
        $this->productDetail = $productDetail;
        $this->auditDetail = $auditDetail;
        $this->productStock = $productStock;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 8;
        $page = $request->page ?? 1;
        $payload = $request->only(['search', 'name', 'status', 'min_variant', 'max_variant', 'from_date', 'until_date']);

        $payload['user_id'] = auth()?->user()?->id;

        if ($request->search) $payload["search"] = $request->search;
        if ($request->outlet_id) $payload["outlet_id"] = $request->outlet_id;

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) {
            $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
        }

        try {
            $collection = $this->auditRepository->customPaginate($per_page, $page, $payload);
            $resources = AuditResource::collection($collection);
            $meta = PaginationHelper::meta($collection);

            return BaseResponse::Paginate("Berhasil mengambil semua audit", $resources, $meta);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), data: null);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AuditRequest $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) {
                $validated['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            }

            $data = $this->service->storeAudit($validated);

            $audit = $this->auditRepository->store($data);

            foreach ($validated['products'] as $index => $product) {
                $productDetail = $this->productDetail->findWithProduct($product['product_detail_id']);

                if (!$productDetail) {
                    return BaseResponse::Error("Inputan produk ke-" . ($index + 1) . " tidak ditemukan.", null);
                }

                // if ($productDetail->product->store_id !== $validated['store_id']) {
                //     return BaseResponse::Error("Inputan produk ke-" . ($index + 1) . " tidak sesuai dengan toko.", null);
                // }
            }

            $mappedDetails = $this->service->mapAuditDetails($validated['products'], $audit);

            foreach ($mappedDetails as $detailData) {
                $this->auditDetail->store($detailData);
            }

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat audit', new AuditResource($audit->load('auditDetails')));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error('Gagal membuat audit: ' . $th->getMessage(), null);
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
                return BaseResponse::Notfound("audit tidak ditemukan");
            }

            $resources = new AuditResource($audit);

            return BaseResponse::Ok("Berhasil mengambil detail audit", $resources);
        } catch (\Throwable $th) {
            return BaseResponse::Error("Terjadi kesalahan: " . $th->getMessage(), null);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(auditRequest $request, $id)
    {

        $audit = $this->auditRepository->show($id);

        if (!$audit) {
            return BaseResponse::Notfound("Audit tidak ditemukan");
        }

        if ($audit->status !== 'pending') {
            return BaseResponse::Error('Audit tidak dapat diubah karena sudah ditanggapi.', null);
        }
        $auditData = $request->validated();

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $auditData['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        DB::beginTransaction();
        try {
            $updateData = [
                'status' => $request->status,
                'reason' => $request->status === 'rejected' ? $request->reason : null,
            ];

            $audit->update($updateData, $auditData);
            $resources = new AuditResource($audit);

            // Jika approved, update stok seperti biasa
            if ($updateData['status'] === 'approved') {
                $outlet = $audit->outlet;
                $details = $audit->auditDetails;

                if ($updateData['status'] === 'approved') {
                    $outlet = $audit->outlet;
                    $details = $audit->auditDetails;

                    foreach ($details as $detail) {
                        $productStock = $this->productStock->findByOutletAndProductDetail($outlet->id, $detail->product_detail_id);
                        $oldStock = $productStock?->stock ?? 0;

                        if ($detail->old_stock == 0) {
                            $detail->old_stock = $oldStock;
                        }

                        $difference = $detail->audit_stock - $oldStock;

                        if ($productStock) {
                            $this->productStock->increaseStock($outlet->id, $detail->product_detail_id, $difference);
                        } else {
                            $this->productStock->store([
                                'outlet_id' => $outlet->id,
                                'product_detail_id' => $detail->product_detail_id,
                                'stock' => $detail->audit_stock,
                            ]);
                        }

                        $this->auditDetail->update($detail->id, [
                            'old_stock' => $detail->old_stock,
                            'audit_stock' => $detail->audit_stock,
                            'unit_id' => $detail->unit_id,
                        ]);
                    }
                }
            }

            DB::commit();
            return BaseResponse::Ok('Status audit berhasil diperbarui', new AuditResource($resources));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error('Gagal memperbarui status audit. ' . $th->getMessage(), null);
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

        $audit = $this->auditRepository->show($id);
        if (!$audit) return BaseResponse::Notfound("Audit tidak ditemukan");

        DB::beginTransaction();

        try {

            if ($audit->status !== 'pending') {
                return BaseResponse::Error('Data tidak dapat dihapus karena Anda sudah memberikan tanggapan.', null);
            }

            $this->auditRepository->delete($id);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus audit', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error('Gagal menghapus audit. ' . $th->getMessage(), null);
        }
    }

    public function list(Request $request)
    {
        try {
            $payload = $request->only(['search', 'name', 'status', 'min_variant', 'max_variant', 'from_date', 'until_date']);

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

            $data = $this->auditRepository->customQuery($payload)->get();
            $resources = AuditResource::collection($data);

            return BaseResponse::Ok("Berhasil mengambil data audit", $resources);
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
        $audit = $this->auditRepository->findTrashed($id);
        if (!$audit) {
            return BaseResponse::Notfound("Sampah audit tidak ditemukan");
        }

        try {
            $restoredAudit = $this->auditRepository->restore($id);
            return BaseResponse::Ok("Audit berhasil dikembalikan", $restoredAudit);
        } catch (\Throwable $th) {
            return BaseResponse::Error("Gagal mengembalikan audit: " . $th->getMessage(), null);
        }
    }

    public function updateStatusWithProducts(AuditRequest $request, $id)
    {
        $audit = $this->auditRepository->show($id);
        if (!$audit) return BaseResponse::Notfound("Audit tidak ditemukan");
        if ($audit->status !== 'pending') return BaseResponse::Error('Audit tidak dapat diubah karena sudah ditanggapi.', null);

        DB::beginTransaction();
        try {

            $updateData = $this->service->updateAuditData($request->all(), $audit);
            $audit->update($updateData);

            $mappedDetails = $this->service->mapAuditDetails($request->products, $audit);
            $outletId = $audit->outlet_id;

            foreach ($mappedDetails as $detail) {
                $productDetailId = $detail['product_detail_id'];
                $auditStock = $detail['audit_stock'];

                $existingAuditDetail = $this->auditDetail->findByAuditAndProductDetail($audit->id, $productDetailId);

                if ($existingAuditDetail) {
                    $this->auditDetail->update($existingAuditDetail->id, $detail);
                } else {
                    $this->auditDetail->store($detail);
                }

                if ($updateData['status'] === 'approved') {
                    $this->productStock->updateOrCreateStock($outletId, $productDetailId, $auditStock);
                }
            }

            DB::commit();
            return BaseResponse::Ok('Status audit berhasil diperbarui', $audit->fresh('auditDetails'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error('Gagal memperbarui status audit: ' . $th->getMessage(), null);
        }
    }
}
