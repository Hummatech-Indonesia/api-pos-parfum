<?php

namespace App\Http\Requests\Dashboard;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */

    public function rules(): array
    {
        $id = $this->route('category') ?? null;
        $store_id = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
        return [
            'name' => [
                'required',
                'max:255',
                Rule::unique('categories','name')->where(function ($query) use ($store_id) {
                    return $query->where('store_id', $store_id)
                    ->where('is_delete', 0);
                })->ignore($id),
            ],
        ];
    }

    /**
     * Custom Validation Messages
     *
     * @return array<string, mixed>
     */

    public function messages(): array
    {
        return [
            'name.required' => 'Nama tidak boleh kosong',
            'name.max' => 'Nama maksimal 255 karakter',
            'name.unique' => 'Nama kategori sudah digunakan di toko ini!'
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(BaseResponse::error("Kesalahan dalam validasi!", $validator->errors()));
    }
}
