<?php

namespace App\Http\Requests;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductBlendRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_blend' => 'required|array',
            'product_blend.*.product_detail_id' => 'required|exists:product_details,id',
            'product_blend.*.unit_id' => 'nullable|exists:units,id',
            'product_blend.*.result_stock' => 'required|numeric|min:1',
            'product_blend.*.description' => 'nullable|string|max:255',

            'product_blend.*.product_blend_details' => 'required|array|min:1',
            'product_blend.*.product_blend_details.*.product_detail_id' => 'required|exists:product_details,id',
            'product_blend.*.product_blend_details.*.used_stock' => 'required|numeric|min:1',
            'product_blend.*.product_blend_details.*.unit_id' => 'nullable|exists:units,id',
        ];
    }

    public function messages(): array
    {
        return [
            'product_blend.required' => 'Data campuran produk wajib diisi.',
            'product_blend.array' => 'Data campuran produk harus berupa array.',

            'product_blend.*.product_detail_id.required' => 'Produk detail wajib dipilih.',
            'product_blend.*.product_detail_id.exists' => 'Produk detail yang dipilih tidak valid.',
            'product_blend.*.description' => 'Deskripsi wajib diisi',
            'product_blend.*.unit_id.exists' => 'Unit yang dipilih tidak valid.',

            'product_blend.*.result_stock.required' => 'Stok hasil wajib diisi.',
            'product_blend.*.result_stock.numeric' => 'Stok hasil harus berupa angka.',
            'product_blend.*.result_stock.min' => 'Stok hasil minimal 1.',

            'product_blend.*.product_blend_details.required' => 'Detail bahan wajib diisi.',
            'product_blend.*.product_blend_details.array' => 'Detail bahan harus berupa array.',
            'product_blend.*.product_blend_details.*.product_detail_id.required' => 'Produk bahan wajib dipilih.',
            'product_blend.*.product_blend_details.*.product_detail_id.exists' => 'Produk bahan yang dipilih tidak valid.',

            'product_blend.*.product_blend_details.*.used_stock.required' => 'Jumlah stok bahan wajib diisi.',
            'product_blend.*.product_blend_details.*.used_stock.numeric' => 'Jumlah stok bahan harus berupa angka.',
            'product_blend.*.product_blend_details.*.used_stock.min' => 'Jumlah stok bahan minimal 1.',

            'product_blend.*.product_blend_details.*.unit_id.exists' => 'Unit yang dipilih tidak valid.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return new HttpResponseException(BaseResponse::error("Kesalahan dalam validasi!", $validator->errors()));
    }
}
