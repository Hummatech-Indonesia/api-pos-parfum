<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengeluaranRequest extends FormRequest
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
            'nama_pengeluaran' => 'required',
            'nominal_pengeluaran' => 'required',
            'deskripsi' => 'required',
            'image' => 'nullable|image|max:2024',
            'kategori_pengeluaran_id' => 'required|exists:kategori_pengeluaran,id',
            'outlet_id' => 'required|exists:outlets,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'category_id' => 'required|exists:categories,id'
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
