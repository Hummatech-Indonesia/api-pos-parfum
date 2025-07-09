<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class StockRequestUpdateRequest extends FormRequest
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
            'status' => 'required|string',
            'stock_requested' => 'sometimes|array',
            'stock_requested.*.product_detail_id' => 'required_with:stock_requested|uuid',
            'stock_requested.*.sended_stock' => 'required_with:stock_requested|integer|min:0',
            'stock_requested.*.price' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status tidak boleh kosong!',
            'status.string' => 'Status harus berupa string!',

            'stock_requested.array' => 'Format permintaan stok tidak valid!',
            'stock_requested.*.product_detail_id.required_with' => 'Produk harus dipilih!',
            'stock_requested.*.product_detail_id.uuid' => 'ID produk tidak valid!',

            'stock_requested.*.sended_stock.required_with' => 'Jumlah stok yang dikirim harus diisi!',
            'stock_requested.*.sended_stock.integer' => 'Jumlah stok harus berupa angka!',
            'stock_requested.*.sended_stock.min' => 'Jumlah stok minimal adalah 0!',

            'stock_requested.*.price.numeric' => 'Harga harus berupa angka!',
            'stock_requested.*.price.min' => 'Harga minimal adalah 0!',
        ];
    }

    public function prepareForValidation()
    {
        // if(!$this->user_id) $this->merge(["user_id" => []]);
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
