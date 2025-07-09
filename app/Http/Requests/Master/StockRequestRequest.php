<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class StockRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'store_name' => 'nullable|string',
            'total_price' => 'nullable|numeric|min:0',
            'store_location' => 'nullable|string',
            'requested_stock' => 'required|array|min:1',
            'requested_stock.*.product_id' => 'nullable|string',
            'requested_stock.*.variant_id' => 'required|uuid|exists:product_details,id',
            'requested_stock.*.requested_stock' => 'required|integer|min:1',
            'requested_stock.*.unit_id' => 'nullable|uuid',
            'requested_stock.*.unit' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Warehouse tidak boleh kosong!',
            'warehouse_id.exists' => 'Warehouse tidak ditemukan!',
            'requested_stock.required' => 'Data permintaan stock wajib diisi!',
            'requested_stock.array' => 'Format permintaan stock tidak valid!',
            'requested_stock.*.variant_id.required' => 'Variant (product detail) wajib diisi!',
            'requested_stock.*.variant_id.exists' => 'Variant (product detail) tidak ditemukan!',
            'requested_stock.*.requested_stock.required' => 'Jumlah stock harus diisi!',
            'requested_stock.*.requested_stock.integer' => 'Jumlah stock harus berupa angka!',
            'requested_stock.*.requested_stock.min' => 'Jumlah stock minimal 1!',
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(
            BaseResponse::Error("Kesalahan dalam validasi", $validator->errors())
        );
    }
}
