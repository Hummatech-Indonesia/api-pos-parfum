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
            'name' => 'required|string|max:255',
            'descriptions' => 'required|string',
            'code' => 'required|string|max:255',
            'value_active' => 'nullable|required_without:value_text|boolean',
            'value_text' => 'nullable|required_without:value_active|string',
            'group' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama tidak boleh lebih dari 255 karakter.',

            'descriptions.required' => 'Deskripsi wajib diisi.',
            'descriptions.string' => 'Deskripsi harus berupa teks.',

            'code.required' => 'Kode wajib diisi.',
            'code.string' => 'Kode harus berupa teks.',
            'code.max' => 'Kode tidak boleh lebih dari 255 karakter.',

            'value_active.required_without' => 'Isi salah satu antara Value Active atau Value Text.',
            'value_active.boolean' => 'Value Active harus berupa nilai true atau false.',

            'value_text.required_without' => 'Isi salah satu antara Value Text atau Value Active.',
            'value_text.string' => 'Value Text harus berupa teks.',

            'group.required' => 'Grup wajib diisi.',
            'group.string' => 'Grup harus berupa teks.',
            'group.max' => 'Grup tidak boleh lebih dari 255 karakter.',
        ];
    }

    protected function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
