<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\BaseRequest;

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
        return [
            'name' => 'required|max:255|unique:categories,name,except,'.$id
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
            'name.max' => 'Nama maksimal 255 karakter'
        ];
    }
}
