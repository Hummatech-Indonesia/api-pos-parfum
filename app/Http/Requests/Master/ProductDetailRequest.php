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
            "product_id" => "required",
            "category_id" => "nullable",
            "product_varian_id" => "nullable",
            "variant_name" => "nullable",
            "material" => "nullable",
            "unit" => "nullable",
            "capacity" => "nullable",
            "weight" => "nullable",
            "density" => "nullable",
            "price" => "nullable",
            "price_discount" => "nullable",
            "product_code" => "nullable",
            "product_image" => "nullable|image|mimes:png,jpg,jpeg|max:2048",
        ];
    }

    public function messages(): array
    {
        return [
            "product_id.required" => "Produk detail harus mencantumkan produk masternya!",
            'product_image' => 'Format detail gambar tidak valid!',
            'product_image' => 'Gambar detail yang bisa dipakai adalah jpg, png, dan jpeg!',
            'product_image' => "Gambar detail maximal adalah 2mb",
        ];
    }
    
    public function failedValidation(Validator $validator){
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam input data!", $validator->errors()));
    }
}
