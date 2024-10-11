<?php

namespace App\Http\Requests\Auth;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class LoginRequest extends FormRequest
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
            "email" => 'required|email',
            'password' => 'required||min:8'
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email tidak boleh kosong!',
            'email.email' => 'Format yang dikirimkan harus berupa email!',
            'password.required' => 'Password tidak boleh kosong!',
            'password.min' => 'Password yang diisikan minimal 8 huruf!'
        ];
    }

    public function failedValidation(Validator $validation): JsonResponse
    {
        throw new HttpResponseException(
            BaseResponse::Error("Kesalahan dalam validasi", $validation->errors())
        );
    }
}
