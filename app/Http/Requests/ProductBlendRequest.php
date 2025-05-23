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
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            
            'product_blend' => 'required|array',
            'product_blend.*.unit_id' => 'required|exists:units,id',
            'product_blend.*.result_stock' => 'required|numeric|min:0',
            // 'product_blend.*.unit_name' => 'required|string',
            // 'product_blend.*.code' => 'required|string|max:255',
            'product_blend.*.image' => 'nullable|image|mimes:png,jpg,jpeg',
            'product_blend.*.unit_type' => 'required|in:weight,volume,unit',
            'product_blend.*.varian_name' => 'required|string|max:255',
            'product_blend.*.category_id' => 'sometimes|exists:categories,id',
            'product_blend.*.price' => 'required|numeric|min:0',

            'product_blend.*.product_blend_details' => 'required|array|min:1',
            'product_blend.*.product_blend_details.*.product_detail_id' => 'required|exists:product_details,id',
            'product_blend.*.product_blend_details.*.used_stock' => 'required|numeric|min:0',

            // 'result_stock' => 'required|numeric|min:0',
            // 'unit_name' => 'sometimes|string|max:255|exists:units,name',
            // 'code' => 'sometimes|string|max:255',
            // 'product_blend_details.*.product_detail_id' => 'required|exists:product_details,id',
            // 'product_blend_details.*.used_stock' => 'required|numeric|min:0',
            // 'image' => 'nullable|image|mimes:png,jpg,jpeg',
            // 'unit_type' => 'required|in:weight,volume,unit',
            // 'varian_name' => 'required|string|max:255',
            // 'category_id' => 'sometimes|exists:categories,id',
            // 'price' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'result_stock.required' => 'Jumlah stok wajib diisi.',
            'result_stock.numeric' => 'Stok harus berupa angka.',
            'date.required' => 'Tanggal wajib diisi.',
            'product_blend_details.*.product_id.required' => 'Kategori wajib diisi',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return new HttpResponseException(BaseResponse::error("Kesalahan dalam validasi!", $validator->errors()));
    }
}
