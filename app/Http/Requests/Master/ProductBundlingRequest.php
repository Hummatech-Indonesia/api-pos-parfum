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
            'harga' => 'nullable|numeric|min:0',
            'kode_Blend' => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'details' => 'required|array|min:1',
            'details.*.product_bundling_material' => 'required|array|min:1',
            'details.*.product_bundling_material.*.product_detail_id' => 'required|uuid|exists:product_details,id',
            'details.*.product_bundling_material.*.quantity' => 'required|numeric|min:1',
            'details.*.product_bundling_material.*.unit_id' => 'nullable|uuid',
            'details.*.product_bundling_material.*.unit' => 'nullable|string|max:255'
        ];
    }




    public function messages(): array
    {
        return [
            'name.required' => 'Nama bundling harus diisi.',

            'harga.numeric' => 'Harga harus berupa angka.',
            'harga.min' => 'Harga tidak boleh negatif.',

            'kode_Blend.string' => 'Kode bundling harus berupa teks.',
            'kode_Blend.max' => 'Kode bundling maksimal 100 karakter.',

            'deskripsi.string' => 'Deskripsi harus berupa teks.',

            'category_id.required' => 'Kategori harus dipilih.',
            'category_id.exists' => 'Kategori tidak ditemukan.',

            'image.string' => 'URL gambar harus berupa teks.',
            'image.image' => 'File valid hanya berupa image!',
            'image.max' => 'Max file 2mb!',
            'image.mimes' => 'Gambar hanya boleh berupa jpg, png, jpeg',

            'details.required' => 'Detail bundling harus diisi.',
            'details.*.product_bundling_material.required' => 'Daftar bahan wajib diisi.',
            'details.*.product_bundling_material.*.product_detail_id.required' => 'ID produk detail wajib diisi.',
            'details.*.product_bundling_material.*.product_detail_id.exists' => 'Produk detail tidak ditemukan.',
            
            // Baru:
            'details.*.product_bundling_material.*.quantity.required' => 'Jumlah quantity wajib diisi.',
            'details.*.product_bundling_material.*.quantity.numeric' => 'Quantity harus berupa angka.',
            'details.*.product_bundling_material.*.quantity.min' => 'Quantity minimal harus 1.',
        ];
    }



    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
