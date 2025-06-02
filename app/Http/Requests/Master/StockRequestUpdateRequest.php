<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class StockRequestUpdateRequest extends FormRequest
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
            'status' => 'required|string',
            'product_detail' => 'required|array',
            'product_detail.*.product_detail_id' => 'required|uuid',
            'product_detail.*.sended_stock' => 'required|integer|min:0',
            'product_detail.*.price' => 'numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status tidak boleh kosong!',
            'status.string' => 'Status harus berupa string!',
            'product_detail.array' => 'Data produk tidak valid!',
            // 'product_detail.*.product_detail_id.unique' => 'Produk tidak ada!',            
            // 'product_detail.*.requested_stock.required' => 'Stock tidak boleh kosong!',
            'product_detail.*.sended_stock.required' => 'Stock tidak boleh kosong!',
            'product_detail.*.sended_stock.integer' => 'Stock harus berupa integer!',
            'product_detail.*.sended_stock.min' => 'Stock minimal adalah 0!',
            'product_detail.*.price.numeric' => 'Price harus berupa angka',
            'product_detail.*.price.min' => 'Price minimal 0',
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
