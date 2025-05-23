<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\AuditDetail;
use App\Models\Outlet;
use App\Models\Product;
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
                        ->update(['stock'=> $totalStock]);
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

    // public function updateAudit(Audit $audit, array $data): Audit

    // {
    //     if ($audit->status !== 'pending') {
    //         throw ValidationException::withMessages([
    //             'status' => ['Audit hanya dapat diubah jika status masih pending.'],
    //         ]);
    //     }

    //     $audit->update($data);

    //     // Jika status menjadi approved, lakukan update stok berdasarkan detail
    //     if (isset($data['status']) && $data['status'] === 'approved') {

    //         // Ambil outlet
    //         $outlet = Outlet::where('store_id', $audit->store_id)->first();

    //         if ($outlet) {
    //             // Ambil semua audit_detail terkait audit ini
    //             $details = $audit->details; // pastikan relasi 'details' didefinisikan

    //             foreach ($details as $detail) {
    //                 $productStock = ProductStock::where('outlet_id', $outlet->id)
    //                     ->where('product_detail_id', $detail->product_detail_id)
    //                     ->first();

    //                 $oldStock = $productStock?->stock ?? 0;

    //                 // Simpan old_stock ke detail jika belum ada
    //                 if ($detail->old_stock == 0) {
    //                     $detail->old_stock = $oldStock;
    //                 }

    //                 $difference = $detail->audit_stock - $oldStock;

    //                 // Update product stock
    //                 if ($productStock) {
    //                     $productStock->stock += $difference;
    //                     $productStock->save();
    //                 } else {
    //                     ProductStock::create([
    //                         'outlet_id' => $outlet->id,
    //                         'product_detail_id' => $detail->product_detail_id,
    //                         'stock' => $detail->audit_stock
    //                     ]);
    //                 }

    //                 $detail->save(); // simpan jika old_stock berubah
    //             }

    //             $audit->save();
    //         }
    //     }

    //     return $audit;
    // }

    // public function updateAuditDetail(AuditDetail $auditDetail, int $audit_stock): AuditDetail
    // {
    //     // Cek status audit dari relasi
    //     if ($auditDetail->audit->status !== 'pending') {
    //         throw ValidationException::withMessages([
    //             'status' => ['Audit hanya dapat diupdate jika status masih pending.'],
    //         ]);
    //     }

    //     $auditDetail->audit_stock = $audit_stock;
    //     $auditDetail->save();

    //     return $auditDetail;
    // }

    // public function deleteAuditDetail(AuditDetail $auditDetail): bool
    // {
    //     // Cek status audit dari relasi
    //     if ($auditDetail->audit->status !== 'pending') {
    //         throw ValidationException::withMessages([
    //             'status' => ['Audit hanya dapat dihapus jika status masih pending.'],
    //         ]);
    //     }

    //     return $auditDetail->delete();
    // }



    // public function storeAudit(array $data)
    // {
    //     $audit = Audit::find($data['audit_id']);
    //     if (!$audit) {
    //         throw ValidationException::withMessages([
    //             'audit_id' => ['Audit tidak ditemukan.'],
    //         ]);
    //     }

    //     // Cek status audit harus pending
    //     if ($audit->status !== 'pending') {
    //         throw ValidationException::withMessages([
    //             'status' => ['Audit detail hanya dapat ditambah jika status masih pending.'],
    //         ]);
    //     }

    //     $outlet = Outlet::where('store_id', $data['store_id'])->first();

    //     if (!$outlet) {
    //         return null;
    //     }

    //     return DB::transaction(function () use ($data, $outlet) {

    //         $productStock = ProductStock::where('outlet_id', $outlet->id)
    //             ->where('product_detail_id', $data['product_detail_id'])
    //             ->first();

    //         $oldStock = $productStock?->stock ?? 0;

    //         AuditDetail::create([
    //             'audit_id' => $data['audit_id'], // harus dikirim dari controller
    //             'product_detail_id' => $data['product_detail_id'],
    //             'old_stock' => $oldStock,
    //             'audit_stock' => $data['audit_stock'],
    //         ]);

    //         // kamu bisa kembalikan audit dengan details kalau perlu
    //         return Audit::with('details')->find($data['audit_id']);
    //     });
    // }

    
    public function storeAudit(array $data)
    {
        return DB::transaction(function () use ($data) {
            $audit = Audit::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'outlet_id' => $data['outlet_id'],
                'store_id' => $data['store_id'],
                'date' => $data['date'],
                'user_id' => auth()->id(), // kalau ingin menyimpan user
                'status' => 'pending', // default
            ]);

            foreach ($data['products'] as $product) {
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
