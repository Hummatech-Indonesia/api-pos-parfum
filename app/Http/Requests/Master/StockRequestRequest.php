<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class StockRequestRequest extends FormRequest
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
            'product_detail_id' => 'required|exists:product_details,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'requested_stock' => 'required|integer|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'product_detail_id.required' => 'Product detail tidak boleh kosong!',
            'warehouse_id.required' => 'Warehouse tidak boleh kosong!',
            'requested_stock.required' => 'Jumlah request tidak boleh kosong!', 
            'requested_stock.min' => 'Jumlah request tidak boleh kosong!'
        ];
    }

    public function prepareForValidation()
    {
        // if(!$this->user_id) $this->merge(["user_id" => []]);
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
