<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\AuditRepository;
use App\Helpers\BaseResponse;
use App\Http\Requests\AuditRequest;
use App\Models\Audit;
use App\Models\AuditDetail;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ProductDetail;
use App\Models\ProductStock;

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
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $validated['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

            $data = $this->service->storeAudit($validated);
            $audit = Audit::create($data);

            foreach ($validated['products'] as $index => $product) {
                $productDetail = ProductDetail::with('product')->find($product['product_detail_id']);

                if (!$productDetail) {
                    return BaseResponse::Error("Inputan produk ke-" . ($index + 1) . " tidak ditemukan.", null);
                }

                if ($productDetail->product->store_id !== $validated['store_id']) {
                    return BaseResponse::Error("Inputan produk ke-" . ($index + 1) . " tidak sesuai dengan toko.", null);
                }
            }
            $mappedDetails = $this->service->mapAuditDetails($validated['products'], $audit);

            foreach ($mappedDetails as $detailData) {
                AuditDetail::create($detailData);
            }

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat audit', $audit->load('details'));
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

            return BaseResponse::Ok("Berhasil mengambil detail setting", $audit);
        } catch (\Throwable $th) {
            return BaseResponse::Error("Terjadi kesalahan: " . $th->getMessage(), null);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AuditRequest $request, $id)
    {
        $audit = $this->auditRepository->show($id);
        if (!$audit) return BaseResponse::Notfound("audit tidak ditemukan");
        DB::beginTransaction();

        try {
            $data = $request->validated();

            if ($audit->status !== 'pending') {
                return BaseResponse::Error('Data tidak dapat diubah karena Anda sudah memberikan tanggapan.', null);
            }

            $updateData = $this->service->updateAuditData($data, $audit);

            $audit->update($updateData);

            $mappedDetails = $this->service->mapAuditDetails($data['products'], $audit);

            foreach ($mappedDetails as $detailData) {
                $detail = $audit->details()
                    ->where('product_detail_id', $detailData['product_detail_id'])
                    ->first();

                if ($detail) {
                    $detail->update([
                        'audit_stock' => $detailData['audit_stock'],
                        'unit_id' => $detailData['unit_id'],
                    ]);
                } else {
                    AuditDetail::create($detailData);
                }
            }

            if (($updateData['status'] ?? $audit->status) === 'approved') {
                $outlet = $audit->outlet;
                $details = $audit->details;

                foreach ($details as $detail) {
                    $productStock = ProductStock::where('outlet_id', $outlet->id)
                        ->where('product_detail_id', $detail->product_detail_id)
                        ->first();

                    $oldStock = $productStock?->stock ?? 0;

                    if ($detail->old_stock == 0) {
                        $detail->old_stock = $oldStock;
                    }

                    $difference = $detail->audit_stock - $oldStock;

                    if ($productStock) {
                        $productStock->stock += $difference;
                        $productStock->save();
                    } else {
                        ProductStock::create([
                            'outlet_id' => $outlet->id,
                            'product_detail_id' => $detail->product_detail_id,
                            'stock' => $detail->audit_stock,
                        ]);
                    }

                    $detail->save();
                }
            }

            DB::commit();
            return BaseResponse::Ok('Audit berhasil diperbarui', $audit->fresh());
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error('Gagal memperbarui audit.  ' . $th->getMessage(), null);
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
            $payload = [];
            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

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
        $audit = Audit::withTrashed()->find($id);
        if (!$audit) return BaseResponse::Notfound("sampah audit tidak ditemukan");
        try {
            $audit = $this->auditRepository->restore($id);
            return BaseResponse::Ok("Audit berhasil dikembalikan", $audit);
        } catch (\Throwable $th) {
            return BaseResponse::Error("Gagal mengembalikan audit: " . $th->getMessage(), null);
        }
    }
}
