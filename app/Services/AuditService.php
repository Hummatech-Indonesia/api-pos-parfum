<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\AuditDetail;
use App\Models\Outlet;
use App\Models\ProductDetail;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuditService
{
    public function updateAudit(Audit $audit, array $data): Audit
    {
        try {
            Log::info('Mulai updateAudit', ['audit_id' => $audit->id, 'data' => $data]);

            if (isset($data['status']) && $data['status'] !== $audit->status) {
                if ($audit->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' => ['Audit hanya bisa diubah jika masih pending.'],
                    ]);
                }
            }

            return DB::transaction(function () use ($audit, $data) {
                Log::info('Update audit utama dimulai');
                $audit->update([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'date' => $data['date'],
                    'outlet_id' => $data['outlet_id'],
                    'status' => $data['status'] ?? $audit->status,
                    'reason' => $data['reason'] ?? null,
                ]);
                Log::info('Update audit utama selesai');

                if (isset($data['products'])) {
                    foreach ($data['products'] as $product) {
                        $detail = $audit->details()
                            ->where('product_detail_id', $product['product_detail_id'])
                            ->first();

                        $productStock = ProductStock::where('outlet_id', $audit->outlet_id)
                            ->where('product_detail_id', $product['product_detail_id'])
                            ->first();

                        $oldStock = $productStock?->stock ?? 0;

                        if ($detail) {
                            $detail->audit_stock = $product['audit_stock'];
                            $detail->unit = $product['unit'];
                            $detail->unit_id = $product['unit_id'];
                            $detail->save();
                        } else {
                            AuditDetail::create([
                                'audit_id' => $audit->id,
                                'product_detail_id' => $product['product_detail_id'],
                                'old_stock' => $oldStock,
                                'audit_stock' => $product['audit_stock'],
                                'unit' => $product['unit'],
                                'unit_id' => $product['unit_id'],

                            ]);
                        }
                    }
                }

                if (($data['status'] ?? $audit->status) === 'approved') {
                    Log::info('Status approved, update stock mulai');
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
                        $totalStock = ProductStock::where('product_detail_id', $detail->product_detail_id)
                            ->sum('stock');

                        ProductDetail::where('id', $detail->product_detail_id)
                            ->update(['stock' => $totalStock]);

                        $detail->save();
                    }
                    Log::info('Update stock selesai');
                }

                return $audit->load('details');
            });
        } catch (ValidationException $ve) {
            Log::warning('ValidationException: ' . json_encode($ve->errors()));
            throw $ve;
        } catch (\Exception $e) {
            Log::error('Error update audit: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new \RuntimeException('Terjadi kesalahan saat memperbarui audit.');
        }
    }


    public function storeAudit(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $audit = Audit::create([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'outlet_id' => $data['outlet_id'],
                    'store_id' => $data['store_id'],
                    'date' => $data['date'],
                    'user_id' => auth()->id(),
                    'status' => 'pending',
                ]);

                foreach ($data['products'] as $index => $product) {

                    $productDetail = ProductDetail::with('product')->find($product['product_detail_id']);

                    if (!$productDetail) {
                        throw new \Exception("Inputan produk ke-" . ($index + 1) . " tidak ditemukan.");
                    }

                    if ($productDetail->product->store_id !== $data['store_id']) {
                        throw new \Exception("Inputan produk ke-" . ($index + 1) . " tidak sesuai dengan toko.");
                    }


                    $productStock = ProductStock::where('product_detail_id', $product['product_detail_id'])
                        ->where('outlet_id', $data['outlet_id'])
                        ->first();

                    $oldStock = $productStock?->stock ?? 0;

                    AuditDetail::create([
                        'audit_id' => $audit->id,
                        'product_detail_id' => $product['product_detail_id'],
                        'old_stock' => $oldStock,
                        'audit_stock' => $product['audit_stock'],
                        'unit' => $product['unit'],
                        'unit_id' => $product['unit_id'],
                    ]);
                }

                return $audit->load('details');
            });
        } catch (\Exception $e) {
            Log::error('Error in storeAudit: ' . $e->getMessage());
            throw $e; // biar errornya muncul dan tidak silent fail
        }
    }


    public function deleteAudit(Audit $audit)
    {
        if ($audit->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Audit hanya dapat dihapus jika status masih pending.'],
            ]);
        }

        DB::transaction(function () use ($audit) {

            $audit->details()->delete();


            $audit->delete();
        });
    }
}
