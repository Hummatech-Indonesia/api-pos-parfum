<?php

namespace App\Http\Requests;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class UserRequest extends FormRequest
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
            "name" => 'required',
            "email" => 'required|email',
            'password' => 'required|min:8',
            'role' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama tidak boleh kosong',
            'email.required' => 'Email tidak boleh kosong!',
            'email.email' => 'Format yang dikirimkan harus berupa email!',
            'role.required' => 'Role user tidak boleh kosong!'
        ];
    }

    public function failedValidation(Validator $validation): JsonResponse
    {
        throw new HttpResponseException(
            BaseResponse::Error("Kesalahan dalam validasi", $validation->errors())
        );
    }

    public function prepareForValidation()
    {
        if(!$this->password) $this->merge(['password' => 'password']);
    }
}
