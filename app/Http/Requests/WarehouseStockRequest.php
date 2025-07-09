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
            'store_name' => 'nullable|string',
            'total_price' => 'nullable|numeric|min:0',
            'store_location' => 'nullable|string',
            'restock' => 'required|array|min:1',
            'restock.*.variant_id' => 'required|uuid|exists:product_details,id',
            'restock.*.requested_stock' => 'required|integer|min:1',
            'restock.*.unit_id' => 'required|uuid|exists:units,id',
        ];
    }

    public function messages(): array
    {
        return [
            'restock.required' => 'Data restock wajib diisi!',
            'restock.*.variant_id.required' => 'Varian produk wajib diisi!',
            'restock.*.requested_stock.required' => 'Jumlah stok wajib diisi!',
            'restock.*.unit_id.required' => 'Satuan wajib diisi!',
        ];
    }


    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
