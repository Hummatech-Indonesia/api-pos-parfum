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
        $id = $this->user;
        return [
            "name" => 'required',
            "email" => 'required|email|unique:users,email,' . $id,
            'password' => 'required|min:8',
            'role' => 'required|array',
            'image' => 'nullable|image|max:1024'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama tidak boleh kosong',
            'email.required' => 'Email tidak boleh kosong!',
            'email.email' => 'Format yang dikirimkan harus berupa email!',
            'email.unique' => 'Email telah digunakan, silahkan masukan kembali email anda!',
            'role.required' => 'Role user tidak boleh kosong!',
            'role.array' => 'Isikan field "role" berupa array data!',
            'image.image' => 'Gambar profil harus berupa image',
            'image.max' => 'Gambar memiliki max 1mb!'
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
