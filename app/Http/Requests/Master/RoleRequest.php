<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class RoleRequest extends FormRequest
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
        $id = $this->route('id'); // ambil ID dari route untuk update
        return [
            "name" => 'required|unique:roles,name' . ($id ? ',' . $id . ',id' : ''),
            'guard_name' => 'required|in:web,api',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama role wajib diisi!',
            'name.unique' => 'Nama role sudah digunakan!',
            'guard_name.required' => 'Guard name wajib diisi!',
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(
            BaseResponse::Error("Validasi gagal!", $validator->errors())
        );
    }
}
