<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends BaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */

    public function rules(): array
    {
        $id = $this->categories;
        $store_id = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;        
        return [
            'name' => [
                'required',
                'max:255',
                Rule::unique('categories','name')->where(function ($query) use ($store_id) {
                    return $query->where('store_id', $store_id);
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
}
