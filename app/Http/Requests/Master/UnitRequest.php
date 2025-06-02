<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class UnitRequest extends FormRequest
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
            'name' => 'required|string',
            'code' => 'required|string|unique:units,code'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama unit tidak boleh kosong!',
            'name.string' => 'Nama harus berupa string',
            'code.required' => 'Kode unit tidak boleh kosong',
            'code.string' => 'Kode harus berupa string',
            'code.unique' => 'Kode sudah digunakan, silahkan menggunakan kode lainnya'
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
