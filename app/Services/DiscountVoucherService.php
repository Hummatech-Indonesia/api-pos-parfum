<?php

namespace App\Services;

use App\Models\DiscountVoucher;
use Illuminate\Http\JsonResponse;
use App\Helpers\BaseResponse;

class DiscountVoucherService
{
    protected $model;

    public function __construct(DiscountVoucher $model)
    {
        $this->model = $model;
    }

    public function showDetail(string $id): JsonResponse
    {
        $voucher = $this->model->with([
            'store:id,name',
            'details.varian:id,name',
            'details.product:id,name',
        ])->find($id);

        if (!$voucher) {
            return BaseResponse::Notfound("Data tidak ditemukan");
        }

        $data = $voucher->toArray();

        // Aman jika details null
        $details = $voucher->details;

        if ($details) {
            $data['details']['varian']['variant_name'] = $details->variant_name ?? null;
            $data['details']['product']['product_code'] = $details->product_code ?? null;

            unset($data['details']['variant_name']);
            unset($data['details']['product_code']);
        }

        return BaseResponse::Ok("Berhasil mengambil detail!", $data);
    }
}
