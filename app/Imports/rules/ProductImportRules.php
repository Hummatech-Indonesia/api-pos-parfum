<?php

namespace App\Imports\Rules;

class ProductImportRules
{
    public static function rules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.category_name' => 'nullable|exists:categories,name',
            '*.stock' => 'required|numeric|min:0',
            '*.price' => 'required|numeric|min:0',
            '*.product_code' => 'required|string|max:50',
        ];
    }

    public static function messages(): array
    {
        return [
            '*.name.required' => 'Nama produk wajib diisi.',
            '*.category_name.exists' => 'Kategori tidak ditemukan.',
            '*.stock.required' => 'Stok tidak boleh kosong.',
            '*.price.required' => 'Harga wajib diisi.',
            '*.product_code.required' => 'Kode produk harus diisi.',
        ];
    }
}
