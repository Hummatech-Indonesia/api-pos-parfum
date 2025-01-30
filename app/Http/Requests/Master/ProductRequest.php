<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
        return [
            "name" => "required",
            "image" => "nullable|image|mimes:png,jpg,jpeg|max:2048",
            "unit_type" => "required|in:weight,volume,unit",
            "qr_code" => "nullable",
            "product_details" => "sometimes|array",
            "product_details.*.product_detail_id" => "nullable",
            "product_details.*.product_id" => "nullable",
            "product_details.*.category_id" => "sometimes|unique:categories,name",
            "product_details.*.product_varian_id" => "sometimes|unique:product_varians,name",
            "product_details.*.material" => "nullable",
            "product_details.*.unit" => "nullable",
            "product_details.*.capacity" => "nullable",
            "product_details.*.weight" => "nullable",
            "product_details.*.density" => "nullable",
            "product_details.*.price" => "nullable",
            "product_details.*.price_discount" => "nullable",
        ];
    }

    public function messages(): array
    {
        return [ 
            'name.required' => 'Nama produk harus di isi!',
            'image.image' => 'Format gambar tidak valid!',
            'image.mimes' => 'Gambar yang bisa dipakai adalah jpg, png, dan jpeg!',
            'image.max' => "Gambar maximal adalah 2mb",
            'unit_type.required' => 'Tipe unit harus diisi!',
            'unit_type.in' => 'Tipe unit yang bidsa dipakai adalah weight, volume, atau unit!',
            'product_details.array' => 'Data produk varian tidak valid!',
            'product_details.*.product_varian_id.unique' => 'Varian ini telah ada, silahkan pilih varian tanpa memembuat ulang!',
            'product_details.*.category_id.unique' => 'Kategori ini telah ada, silahkan pilih kategori tanpa memembuat ulang!'
        ];
    }

    public function failedValidation(Validator $validator){
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam input data!", $validator->errors()));
    }

    public function prepareForValidation()
    {
        if(!$this->product_details) $this->merge(["product_details" => []]);
        if(!$this->qr_code) $this->merge(["qr_code" => null]);
    }
}
