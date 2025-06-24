<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
        $store_id = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
        return [
            "name" => "required|string",
            "image" => "nullable|image|mimes:png,jpg,jpeg|max:2048",
            "unit_type" => "required|in:weight,volume,unit",
            "qr_code" => "nullable|string",
            "category_id" => "required|exists:categories,id",

            "product_details" => "sometimes|array",
            "product_details.*.product_id" => "nullable|uuid|exists:products,id",
            "product_details.*.category_id" => "required|exists:categories,id",
            "product_details.*.material" => "nullable|string",
            "product_details.*.unit" => "nullable|string",
            "product_details.*.stock" => "required|numeric|min:0",
            "product_details.*.capacity" => "nullable|numeric|min:0",
            "product_details.*.weight" => "nullable|numeric|min:0",
            "product_details.*.density" => "nullable|numeric|min:0",
            "product_details.*.price" => "required|numeric|min:0",
            "product_details.*.price_discount" => "nullable|numeric|min:0",
            "product_details.*.product_code" => "required|string",
            "product_details.*.product_image" => "nullable|image|mimes:png,jpg,jpeg|max:2048",
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama produk harus di isi!',
            'image.image' => 'Format gambar tidak valid!',
            'image.mimes' => 'Gambar yang bisa dipakai adalah jpg, png, dan jpeg!',
            'image.max' => "Gambar maksimal adalah 2mb",
            'category_id.required' => 'Kategori harus diisi!',
            'category_id.exists' => 'Kategori tidak ada!',
            'unit_type.required' => 'Tipe unit harus diisi!',
            'unit_type.in' => 'Tipe unit yang bidsa dipakai adalah weight, volume, atau unit!',
            'product_details.array' => 'Data produk varian tidak valid!',
            'product_details.product_image' => 'Format detail gambar tidak valid!',
            'product_details.product_image|mimes:png,jpg,jpeg' => 'Gambar detail yang bisa dipakai adalah jpg, png, dan jpeg!',
            'product_details.product_image|max:2048' => "Gambar detail maksimal adalah 2mb",
            'product_details.*.product_image.required' => 'Gambar produk harus diunggah!',
            'product_details.*.category_id.required' => 'Kategori pada detail produk harus diisi!',
            'product_details.*.product_code' => 'Kode produk harus diisi!',
            'description.string' => 'Deskripsi produk harus berupa teks!',
            // 'product_details.*.product_varian_id.unique' => 'Varian ini telah ada, silahkan pilih varian tanpa memembuat ulang!',
            // 'product_details.*.category_id.unique' => 'Kategori ini telah ada, silahkan pilih kategori tanpa memembuat ulang!'
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam input data!", $validator->errors()));
    }

    public function prepareForValidation()
    {
        if (!$this->product_details) $this->merge(["product_details" => []]);
        if (!$this->qr_code) $this->merge(["qr_code" => null]);
    }
}
