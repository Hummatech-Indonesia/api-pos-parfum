<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class ProductBundlingDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_detail_id' => 'required|uuid|exists:product_details,id',
            'unit' => 'nullable|string',
            'unit_id' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'product_detail_id.required' => 'Product detail harus dipilih.',
            'product_detail_id.uuid' => 'Format Product Detail ID tidak valid.',
            'product_detail_id.exists' => 'Product detail tidak ditemukan di dalam database.',
            'unit.string' => 'Unit harus berupa teks.',
            'unit_id.uuid' => 'Format Unit ID tidak valid.',
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }

}
