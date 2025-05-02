<?php

namespace App\Http\Requests;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class TransactionRequest extends FormRequest
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
            'transaction_detail' => 'required|array',
            'transaction_detail.*.product_detail_id' => 'required|exists:product_details,id',
            'transaction_detail.*.price' => 'required|min:1',
            'transaction_detail.*.quantity' => 'required|min:1',
            'transaction_detail.*.unit' => 'required',
            'discounts' => 'sometimes|array',
            'discounts.*' => 'sometimes|exists:discount_vouchers,id',
            'user_id' => 'sometimes|exists:discount_vouchers,id',
            'user_name' => 'sometimes',
            'amount_price' => 'required|min:1',
            'tax' => 'required|min:0',
            'amount_tax' => 'required|min:0',
            'payment_method' => 'required',
            'note' => 'nullable' 
        ];
    }

    public function messages(): array 
    {
        return [
        
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }

    public function prepareForValidation()
    {
        if(!$this->discounts) $this->merge(["discounts" => []]);
    }
}
