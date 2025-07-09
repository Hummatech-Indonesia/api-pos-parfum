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
        $user = auth()->user();
        $id = $this->route('category') ?? null;
        $store_id = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        $uniqueRule = Rule::unique('categories', 'name')
            ->where(function ($query) use ($store_id, $user) {
                $query->where('store_id', $store_id)
                    ->where('is_delete', 0);

                if ($user->hasRole('warehouse')) {
                    $query->where('warehouse_id', $user->warehouse_id);
                } elseif ($user->hasRole('outlet')) {
                    $query->where('outlet_id', $user->outlet_id);
                }
            });

        if ($id) {
            $uniqueRule->ignore($id);
        }

        return [
            'name' => ['required', 'max:255', $uniqueRule],
            'store_id' => ['required', 'uuid'],
            'outlet_id' => ['nullable', 'uuid'],
            'warehouse_id' => ['nullable', 'uuid'],
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

    protected function prepareForValidation()
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole('outlet')) {
                $this->merge([
                    'outlet_id' => auth()->user()->outlet_id,
                ]);
            } elseif (auth()->user()->hasRole('warehouse')) {
                $this->merge([
                    'warehouse_id' => auth()->user()->warehouse_id,
                ]);
            }

            $this->merge([
                'store_id' => auth()->user()->store_id ?? auth()->user()->store?->id,
            ]);
        }
    }
}
