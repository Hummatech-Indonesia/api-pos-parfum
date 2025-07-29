<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class KategoriPengeluaranRequest extends FormRequest
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
            'nama' => 'required',
            'outlet_id' => 'required|exists:outlets,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kategori pengeluaran tidak boleh kosong!',
            'outlet_id.required' => 'Outlet tidak boleh kosong!',
            'warehouse_id.required' => 'Gudang tidak boleh kosong!',
            'outlet_id.exists' => 'Outlet yang dipilih tidak valid!',
            'warehouse_id.exists' => 'Warehouse yang dipilih tidak valid!',
        ];
    }
}
