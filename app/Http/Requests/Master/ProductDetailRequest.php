<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductDetailRequest extends FormRequest
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
            "product_id" => "required|uuid|exists:products,id",
            "category_id" => "required|exists:categories,id",
            "product_varian_id" => "nullable|uuid|exists:product_varians,id",
            "variant_name" => "required|string",
            "material" => "nullable|string",
            "unit" => "nullable|string",
            "capacity" => "nullable|numeric|min:0",
            "weight" => "nullable|numeric|min:0",
            "density" => "nullable|numeric|min:0",
            "price" => "required|numeric|min:0",
            "stock" => "required|numeric|min:0",
            "price_discount" => "nullable|numeric|min:0",
            "product_code" => "required|string",
            "product_image" => "nullable|image|mimes:png,jpg,jpeg|max:2048",
        ];
    }

    public function messages(): array
    {
        return [
            "product_id.required" => "Produk detail harus mencantumkan produk masternya!",
            "product_id.uuid" => "Format ID produk tidak valid!",
            "product_id.exists" => "Produk master tidak ditemukan!",

            "category_id.exists" => "Kategori tidak ditemukan.",
            "product_varian_id.exists" => "Varian produk tidak ditemukan.",

            "product_image.image" => "File harus berupa gambar.",
            "product_image.mimes" => "Format gambar harus jpg, jpeg, atau png.",
            "product_image.max" => "Ukuran gambar maksimal 2MB.",

            // 'product_image.required' => 'Gambar produk harus diunggah!',
            'category_id.required' => 'Kategori pada detail produk harus diisi!',
            'variant_name.required' => 'Nama varian harus diisi!',
            'stock.required' => 'Stok harus diisi!',
            'product_code.required' => 'Kode produk harus diisi!',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam input data!", $validator->errors()));
    }
}
