<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class OutletRequest extends FormRequest
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
            'name' => 'required',
            'address' => 'required',
            'telp' => 'sometimes|nullable|min:10',
            'user_id' => 'sometimes|nullable|array'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama outlet tidak boleh kosong!',
            'address.required' => 'Alamat outlet tidak boleh kosong',
            'telp.min' => 'Nomor telefon tidak valid!',
            'user_id.array' => 'User yang terdaftar tidak valid!'
        ];
    }

    public function prepareForValidation()
    {
        if(!$this->address) $this->merge(["address" => '-']);
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
