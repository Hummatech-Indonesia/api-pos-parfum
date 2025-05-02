<?php

namespace App\Http\Requests\Transaction;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class TransactionRequest extends FormRequest
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
            'transaction_detail' => 'required|array',
            'transaction_detail.*.product_detail_id' => 'required|exists:product_details,id',
            'transaction_detail.*.price' => 'required|min:1',
            'transaction_detail.*.quantity' => 'required|min:1',
            'transaction_detail.*.unit' => 'required',
            'discounts' => 'sometimes|array',
            'discounts.*' => 'sometimes|exists:discount_vouchers,id',
            'user_id' => 'sometimes|exists:discount_vouchers,id',
            'user_name' => 'sometimes',
            'amount_price' => 'required|min:1',
            'tax' => 'required|min:0',
            'amount_tax' => 'required|min:0',
            'payment_method' => 'required',
            'note' => 'nullable' 
        ];
    }

    public function messages(): array 
    {
        return [
            'transaction_detail.required' => 'Pilih produk yang ingin dibeli!',
            'transaction_detail.array' => 'Produk yang dikirim tidak valid!',
            'transaction_detail.*.product_detail_id.required' => 'Produk harus di pilih terlebih dahulu!',
            'transaction_detail.*.product_detail_id.exists' => 'Produk yang di pilih tidak terdaftar, silahkan pilih ulang!',
            'transaction_detail.*.price.required' => 'Harga produk harus di isi terlebih dahulu!',
            'transaction_detail.*.price.min' => 'Harga produk minimal Rp.1!',
            'transaction_detail.*.quantity.required' => 'Jumlah produk harus di isi terlebih dahulu!',
            'transaction_detail.*.quantity.min' => 'Jumlah produk minimal 1!',
            'transaction_detail.*.unit.required' => 'Unit pembelian produk harus di isi!',
            'amount_price.required' => 'Harga total produk harus di isi terlebih dahulu!',
            'amount_price.min' => 'Harga total produk minimal 1!',
            'tax.required' => 'Pajak persen harus di isi terlebih dahulu!',
            'tax.min' => 'Pajak persen minimal 0!',
            'amount_tax.required' => 'Total pajak harus di isi terlebih dahulu!',
            'amount_tax.min' => 'Total pajak minimal 0!',
            'user_id.sometimes' => 'User member harus di pilih terlebih dahulu!',
            'user_id.exists' => 'User member yang di pilih tidak terdaftar, silahkan pilih ulang!',
            'payment_method.required' => 'Metode pembayaran harus diisi!',
            'user_name.sometimes' => 'Nama pembeli harus diisi!',
            'discounts.sometimes' => 'Pilih discount yang ingin digunakan!',
            'discounts.array' => 'Discount yang dikirim tidak valid!',
            'discounts.*.sometimes' => 'Discount harus di pilih terlebih dahulu!',
            'discounts.*.exists' => 'Discount yang di pilih tidak terdaftar, silahkan pilih ulang!',
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }

    public function prepareForValidation()
    {
        if(!$this->discounts) $this->merge(["discounts" => []]);
    }
}
