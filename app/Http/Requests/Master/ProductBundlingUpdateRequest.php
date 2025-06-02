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
            // 'name' => 'required|string|max:255',
            // 'description' => 'required|string',
            // 'category_id' => 'required|exists:categories,id',

            'details' => 'required|array|min:1',
            'details.*.unit' => 'required|string',
            'details.*.unit_id' => 'required|uuid|exists:units,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
            'details.*.product_detail_id' => 'required|uuid|exists:product_details,id',
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
