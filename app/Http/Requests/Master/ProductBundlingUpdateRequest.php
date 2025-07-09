<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class ProductBundlingUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Set true agar bisa dipakai
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'harga' => 'sometimes|required|numeric|min:0',
            'kode_Blend' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'quantity' => 'sometimes|required|numeric|min:0',

            'details' => 'array|min:1',
            'details.*.product_detail_id' => 'uuid|exists:product_details,id',

            'details.*.unit' => 'nullable|string',
            'details.*.unit_id' => 'nullable|uuid|exists:units,id',
            'details.*.quantity' => 'nullable|numeric|min:0.01',
        ];
    }



    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama bundling harus diisi.',
            'name.string' => 'Nama bundling harus berupa teks.',
            'name.max' => 'Nama bundling tidak boleh lebih dari 255 karakter.',
            
            'harga.min' => 'Harga tidak boleh negatif.',

            'description.required' => 'Deskripsi bundling harus diisi.',
            'description.string' => 'Deskripsi bundling harus berupa teks.',

            'category_id.required' => 'Kategori harus dipilih.',
            'category_id.exists' => 'Kategori tidak ditemukan di database.',

            'details.required' => 'Detail produk bundling harus diisi.',
            'details.array' => 'Format detail produk bundling tidak valid.',
            'details.min' => 'Minimal harus ada satu detail produk bundling.',

            'details.*.unit.required' => 'Unit untuk setiap detail wajib diisi.',
            'details.*.unit.string' => 'Unit harus berupa teks.',

            'details.*.unit_id.required' => 'Unit ID wajib diisi untuk setiap detail.',
            'details.*.unit_id.uuid' => 'Unit ID harus dalam format UUID.',
            'details.*.unit_id.exists' => 'Unit ID tidak ditemukan di database.',

            'details.*.quantity.required' => 'Kuantitas produk wajib diisi.',
            'details.*.quantity.numeric' => 'Kuantitas harus berupa angka.',
            'details.*.quantity.min' => 'Kuantitas minimal harus lebih dari 0.',

            'details.*.product_detail_id.required' => 'Product detail ID wajib diisi.',
            'details.*.product_detail_id.uuid' => 'Product detail ID harus dalam format UUID.',
            'details.*.product_detail_id.exists' => 'Product detail ID tidak ditemukan di database.',
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
