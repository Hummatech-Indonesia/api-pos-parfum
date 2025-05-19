<?php

namespace App\Http\Requests;

use App\Helpers\BaseResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;

class SettingRequest extends FormRequest
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
            'name' => 'nullable|string|max:255',
            'descriptions' => 'nullable|string',
            'code' => 'nullable|string|max:255',
            'value_active' => 'required|boolean',
            'value_text' => 'required|string',
            'group' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.max' => 'Nama tidak boleh lebih dari 255 karakter.',    
            'code.max' => 'Kode tidak boleh lebih dari 255 karakter.',
            'value_active.required' => 'Status aktif wajib diisi.',
            'value_active.boolean' => 'Status aktif harus berupa true atau false.',
            'value_text.required' => 'Nilai teks wajib diisi.',
            'group.max' => 'Grup tidak boleh lebih dari 255 karakter.',
        ];
    }

    protected function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
