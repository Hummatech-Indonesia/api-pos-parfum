<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class UserSyncRequest extends FormRequest
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
            'users' => 'required|array',
            "users.*.name" => 'required',
            "users.*.phone" => 'required',
            'users.*.image' => 'nullable|image|max:1024'
        ];
    }

    public function messages(): array
    {
        return [
            'users.required' => 'Data user harus dikirimkan!',
            'users.array' => 'Data user harus berupa array!',
            'users.*.name.required' => 'Nama tidak boleh kosong',
            'users.*.phone.required' => 'Nomor telephone',
            // 'users.*.email.required' => 'Email tidak boleh kosong!',
            // 'users.*.email.email' => 'Format yang dikirimkan harus berupa email!',
            'users.*.image.image' => 'Gambar profil harus berupa image',
            'users.*.image.max' => 'Gambar memiliki max 1mb!'
            // 'email.unique' => 'Email telah digunakan, silahkan masukan kembali email anda!',
        ];
    }

    public function failedValidation(Validator $validation): JsonResponse
    {
        throw new HttpResponseException(
            BaseResponse::Error("Kesalahan dalam validasi", $validation->errors())
        );
    }
}
