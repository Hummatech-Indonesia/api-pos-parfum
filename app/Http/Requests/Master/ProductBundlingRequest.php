<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class ProductBundlingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product' => 'required|array',
            'product.name' => 'required|string|max:255',
            'product.store_id' => 'required|uuid|exists:stores,id',
            'product.unit_type' => 'required|in:weight,volume,unit',
            'product.image' => 'nullable|string',
            'product.qr_code' => 'nullable|string',
            'product.category_id' => 'nullable|exists:categories,id',

            'name' => 'required|string|max:255',
            'description' => 'required|string',
            // 'category_id' => 'required|exists:categories,id',

            'details' => 'required|array|min:1',

            'details.*.unit' => 'required|string',
            'details.*.unit_id' => 'required|uuid|exists:units,id',
            'details.*.quantity' => 'required|numeric|min:0.01',

            'details.*.product_detail' => 'required|array',
            // 'details.*.product_detail.category_id' => 'required|exists:categories,id',
            'details.*.product_detail.product_varian_id' => 'nullable|uuid|exists:product_varians,id',
            'details.*.product_detail.material' => 'required|string|max:255',
            'details.*.product_detail.unit' => 'required|string|max:50',
            'details.*.product_detail.capacity' => 'required|numeric|min:0',
            'details.*.product_detail.weight' => 'required|numeric|min:0',
            'details.*.product_detail.density' => 'required|numeric|min:0',
            'details.*.product_detail.price' => 'required|numeric|min:0',
            'details.*.product_detail.price_discount' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'product.required' => 'Data produk harus disertakan.',
            'product.name.required' => 'Nama produk harus diisi.',
            'product.store_id.required' => 'Store harus dipilih.',
            'product.store_id.exists' => 'Store tidak ditemukan.',
            'product.unit_type.required' => 'Tipe unit harus diisi.',
            'product.unit_type.in' => 'Tipe unit harus salah satu dari: weight, volume, unit.',

            'name.required' => 'Nama bundling harus diisi.',
            'description.required' => 'Deskripsi bundling harus diisi.',
            'category_id.required' => 'Kategori harus dipilih.',
            'category_id.exists' => 'Kategori tidak ditemukan.',

            'details.required' => 'Detail produk bundling harus diisi.',
            'details.*.unit.required' => 'Jumlah unit wajib diisi.',
            'details.*.unit_id.required' => 'Unit ID wajib diisi.',
            'details.*.unit_id.exists' => 'Unit ID tidak ditemukan.',
            'details.*.quantity.required' => 'Kuantitas produk wajib diisi.',
            'details.*.quantity.numeric' => 'Kuantitas harus berupa angka.',
            'details.*.quantity.min' => 'Kuantitas minimal harus lebih dari 0.',

            'details.*.product_detail.required' => 'Data produk detail wajib diisi.',
            'details.*.product_detail.category_id.required' => 'Kategori produk detail wajib dipilih.',
            'details.*.product_detail.category_id.exists' => 'Kategori produk detail tidak ditemukan.',
            'details.*.product_detail.material.required' => 'Material produk wajib diisi.',
            'details.*.product_detail.unit.required' => 'Unit produk wajib diisi.',
            'details.*.product_detail.capacity.required' => 'Kapasitas produk wajib diisi.',
            'details.*.product_detail.weight.required' => 'Berat produk wajib diisi.',
            'details.*.product_detail.density.required' => 'Densitas produk wajib diisi.',
            'details.*.product_detail.price.required' => 'Harga produk wajib diisi.',
            'details.*.product_detail.price_discount.numeric' => 'Diskon harga harus berupa angka.',
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
