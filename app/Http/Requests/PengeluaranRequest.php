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
            'category_id' => 'required|exists:categories,id'
        ];
    }

    public function messages(): array
    {
        return [
            'nama_pengeluaran.required' => 'Nama pengeluaran tidak boleh kosong!',
            'nominal_pengeluaran.required' => 'Nominal pengeluaran tidak boleh kosong!',
            'deskripsi.required' => 'Deskripsi pengeluaran tidak boleh kosong!',
            'image.image' => 'File yang diunggah harus berupa gambar!',
            'image.max' => 'Ukuran gambar tidak boleh lebih dari 2MB!',
            'kategori_pengeluaran_id.required' => 'Kategori pengeluaran tidak boleh kosong!',
            'kategori_pengeluaran_id.exists' => 'Kategori pengeluaran yang dipilih tidak valid!',
            'category_id.required' => 'Kategori tidak boleh kosong!',
            'category_id.exists' => 'Kategori yang dipilih tidak valid!'
        ];
    }
}
