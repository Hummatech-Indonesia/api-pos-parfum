<?php

namespace App\Http\Requests\Transaction;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class TransactionSyncRequest extends FormRequest
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
            'transaction' => 'required|array',
            'transaction.*.transaction_detail' => 'required|array',
            'transaction.*.transaction_detail.*.product_detail_id' => 'required|exists:product_details,id',
            'transaction.*.transaction_detail.*.price' => 'required|min:1',
            'transaction.*.transaction_detail.*.quantity' => 'required|min:1',
            'transaction.*.transaction_detail.*.unit' => 'required',
            'transaction.*.discounts' => 'sometimes|array',
            'transaction.*.discounts.*' => 'sometimes|exists:discount_vouchers,id',
            'transaction.*.user_id' => 'sometimes|exists:discount_vouchers,id',
            'transaction.*.user_name' => 'sometimes',
            'transaction.*.amount_price' => 'required|min:1',
            'transaction.*.tax' => 'required|min:0',
            'transaction.*.amount_tax' => 'required|min:0',
            'transaction.*.payment_method' => 'required',
            'transaction.*.note' => 'nullable' 
        ];
    }

    public function messages(): array 
    {
        return [
            'transaction.required' => 'Transaksi harus dikirimkan!',
            'transaction.array' => 'Transaksi yang dikirim tidak valid!',
            'transaction.*.transaction_detail.required' => 'Pilih produk yang ingin dibeli!',
            'transaction.*.transaction_detail.array' => 'Produk yang dikirim tidak valid!',
            'transaction.*.transaction_detail.*.product_detail_id.required' => 'Produk harus di pilih terlebih dahulu!',
            'transaction.*.transaction_detail.*.product_detail_id.exists' => 'Produk yang di pilih tidak terdaftar, silahkan pilih ulang!',
            'transaction.*.transaction_detail.*.price.required' => 'Harga produk harus di isi terlebih dahulu!',
            'transaction.*.transaction_detail.*.price.min' => 'Harga produk minimal Rp.1!',
            'transaction.*.transaction_detail.*.quantity.required' => 'Jumlah produk harus di isi terlebih dahulu!',
            'transaction.*.transaction_detail.*.quantity.min' => 'Jumlah produk minimal 1!',
            'transaction.*.transaction_detail.*.unit.required' => 'Unit pembelian produk harus di isi!',
            'transaction.*.amount_price.required' => 'Harga total produk harus di isi terlebih dahulu!',
            'transaction.*.amount_price.min' => 'Harga total produk minimal 1!',
            'transaction.*.tax.required' => 'Pajak persen harus di isi terlebih dahulu!',
            'transaction.*.tax.min' => 'Pajak persen minimal 0!',
            'transaction.*.amount_tax.required' => 'Total pajak harus di isi terlebih dahulu!',
            'transaction.*.amount_tax.min' => 'Total pajak minimal 0!',
            'transaction.*.user_id.sometimes' => 'User member harus di pilih terlebih dahulu!',
            'transaction.*.user_id.exists' => 'User member yang di pilih tidak terdaftar, silahkan pilih ulang!',
            'transaction.*.payment_method.required' => 'Metode pembayaran harus diisi!',
            'transaction.*.user_name.sometimes' => 'Nama pembeli harus diisi!',
            'transaction.*.discounts.sometimes' => 'Pilih discount yang ingin digunakan!',
            'transaction.*.discounts.array' => 'Discount yang dikirim tidak valid!',
            'transaction.*.discounts.*.sometimes' => 'Discount harus di pilih terlebih dahulu!',
            'transaction.*.discounts.*.exists' => 'Discount yang di pilih tidak terdaftar, silahkan pilih ulang!',
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }

    public function prepareForValidation()
    {
        
    }
}
