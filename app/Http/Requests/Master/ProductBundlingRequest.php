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
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:1',
            'harga' => 'required|numeric|min:0',
            'kode_Blend' => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|string',

            'details' => 'required|array|min:1',
            'details.*.product_bundling_material' => 'required|array|min:1',
            'details.*.product_bundling_material.*.product_detail_id' => 'required|uuid|exists:product_details,id',
        ];
    }



    public function messages(): array
    {
        return [
            'name.required' => 'Nama bundling harus diisi.',
            'quantity.required' => 'Stok bundling harus diisi.',
            'quantity.numeric' => 'Stok harus berupa angka.',
            'quantity.min' => 'Stok minimal harus 1.',

            'harga.required' => 'Harga bundling harus diisi.',
            'harga.numeric' => 'Harga harus berupa angka.',
            'harga.min' => 'Harga tidak boleh negatif.',

            'kode_Blend.string' => 'Kode bundling harus berupa teks.',
            'kode_Blend.max' => 'Kode bundling maksimal 100 karakter.',

            'deskripsi.string' => 'Deskripsi harus berupa teks.',

            'category_id.required' => 'Kategori harus dipilih.',
            'category_id.exists' => 'Kategori tidak ditemukan.',

            'image.string' => 'URL gambar harus berupa teks.',

            'details.required' => 'Detail bundling harus diisi.',
            'details.*.product_bundling_material.required' => 'Daftar bahan wajib diisi.',
            'details.*.product_bundling_material.*.product_detail_id.required' => 'ID produk detail wajib diisi.',
            'details.*.product_bundling_material.*.product_detail_id.exists' => 'Produk detail tidak ditemukan.',
        ];
    }


    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
