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
            // 'date' => 'required|date',

            'product_blend' => 'required|array',
            'product_blend.*.product_detail_id' => 'required|exists:product_details,id',
            // 'product_blend.*.unit_id' => 'required|exists:units,id',
            'product_blend.*.result_stock' => 'required|numeric|min:0',
            // 'product_blend.*.image' => 'nullable|image|mimes:png,jpg,jpeg',
            // 'product_blend.*.unit_type' => 'required|in:weight,volume,unit',
            // 'product_blend.*.varian_name' => 'required|string|max:255',
            // 'product_blend.*.category_id' => 'sometimes|exists:categories,id',
            // 'product_blend.*.price' => 'required|numeric|min:0',
            'product_blend.*.description' => 'nullable|string|max:255',

            'product_blend.*.product_blend_details' => 'required|array|min:1',
            'product_blend.*.product_blend_details.*.product_detail_id' => 'required|exists:product_details,id',
            'product_blend.*.product_blend_details.*.used_stock' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama produk wajib diisi.',
            'name.string' => 'Nama produk harus berupa teks.',
            'name.max' => 'Nama produk maksimal 255 karakter.',

            // 'date.required' => 'Tanggal wajib diisi.',
            // 'date.date' => 'Format tanggal tidak valid.',

            'product_blend.required' => 'Data campuran produk wajib diisi.',
            'product_blend.array' => 'Data campuran produk harus berupa array.',

            'product_blend.*.product_detail_id.required' => 'Produk detail wajib dipilih.',
            'product_blend.*.product_detail_id.exists' => 'Produk detail yang dipilih tidak valid.',
            // 'product_blend.*.unit_id.required' => 'Unit wajib dipilih.',
            // 'product_blend.*.unit_id.exists' => 'Unit yang dipilih tidak valid.',
            'product_blend.*.description' => 'Deskripsi wajib diisi',

            'product_blend.*.result_stock.required' => 'Stok hasil wajib diisi.',
            'product_blend.*.result_stock.numeric' => 'Stok hasil harus berupa angka.',
            'product_blend.*.result_stock.min' => 'Stok hasil minimal 0.',

            // 'product_blend.*.image.image' => 'File harus berupa gambar.',
            // 'product_blend.*.image.mimes' => 'Gambar harus berformat png, jpg, atau jpeg.',

            // 'product_blend.*.unit_type.required' => 'Tipe unit wajib dipilih.',
            // 'product_blend.*.unit_type.in' => 'Tipe unit harus berupa weight, volume, atau unit.',

            // 'product_blend.*.varian_name.required' => 'Nama varian wajib diisi.',
            // 'product_blend.*.varian_name.string' => 'Nama varian harus berupa teks.',
            // 'product_blend.*.varian_name.max' => 'Nama varian maksimal 255 karakter.',

            // 'product_blend.*.category_id.exists' => 'Kategori yang dipilih tidak valid.',

            // 'product_blend.*.price.required' => 'Harga wajib diisi.',
            // 'product_blend.*.price.numeric' => 'Harga harus berupa angka.',
            // 'product_blend.*.price.min' => 'Harga minimal 0.',

            'product_blend.*.product_blend_details.required' => 'Detail bahan wajib diisi.',
            'product_blend.*.product_blend_details.array' => 'Detail bahan harus berupa array.',
            'product_blend.*.product_blend_details.*.product_detail_id.required' => 'Produk bahan wajib dipilih.',
            'product_blend.*.product_blend_details.*.product_detail_id.exists' => 'Produk bahan yang dipilih tidak valid.',

            'product_blend.*.product_blend_details.*.used_stock.required' => 'Jumlah stok bahan wajib diisi.',
            'product_blend.*.product_blend_details.*.used_stock.numeric' => 'Jumlah stok bahan harus berupa angka.',
            'product_blend.*.product_blend_details.*.used_stock.min' => 'Jumlah stok bahan minimal 0.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return new HttpResponseException(BaseResponse::error("Kesalahan dalam validasi!", $validator->errors()));
    }
}
