<?php

namespace App\Http\Requests\Auth;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class RegisterRequest extends FormRequest
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
            "email" => 'required|email|unique:users,email', 
            'password' => 'required|min:8|confirmed',
            // 'password_confirmation' => 'required|same:password',
            'name_store' => 'required',
            'address_store' => 'required',
            'logo' => 'nullable|mimes:png,jpg,jpeg',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama tidak boleh kosong',
            'email.required' => 'Email tidak boleh kosong!',
            'email.email' => 'Format yang dikirimkan harus berupa email!',
            'email.unique' => 'Email telah digunakan, silahkan masukan kembali email anda!',
            'password.required' => 'Password tidak boleh kosong!',
            'password.min' => 'Password yang diisikan minimal 8 huruf!',
            'password_confirmation.required' => 'Password konfirmasi tidak boleh kosong!',
            'password_confirmation.same' => 'Password konfirmasi harus sama dengan password yang diisikan!',
            'name_store.required' =>  'Nama toko tidak boleh kosong',
            'address_store.required' =>  'Alamat toko tidak boleh kosong',
        ];
    }

    public function failedValidation(Validator $validation): JsonResponse
    {
        throw new HttpResponseException(
            BaseResponse::Error("Kesalahan dalam validasi", $validation->errors())
        );
    }
}
