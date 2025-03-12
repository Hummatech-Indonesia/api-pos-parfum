<?php

namespace App\Http\Requests;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class WarehouseStockRequest extends FormRequest
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
            'product_detail_id' => 'required',
            'stock' => 'required',
            'reason' => 'required'
        ];
    }

    public function messages(): array 
    {
        return [
            'product_detail_id.required' => "Produk harus di pilih terlebih dahulu!",
            'stock' => 'Stok yang akan di kirimkan harus di isi!',
            'reason' => 'Catatan harus di isi!'
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
