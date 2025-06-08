<?php

namespace App\Http\Requests\Master;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DiscountVoucherRequest extends FormRequest
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
            "store_id" => 'nullable',
            'product_id' => 'nullable',
            'product_detail_id' => 'nullable',
            'outlet_id' => 'nullable',
            'name' => 'required',
            'desc' => 'nullable',
            'max_used' => 'nullable',
            'min' => 'nullable',
            'discount' => 'required|integer|min:0',
            'expired' => 'sometimes|after:today',
            'active' => 'nullable'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama discount / voucher harus diisi!',
            'discount.required' => 'Jumlah discount harus diisi!',
            'expired.after' => 'Tenggat discount / voucher harus melebihi dari hari ini!'
        ];
    }

    public function prepareForValidation()
    {
        // if(!$this->store_id) $this->merge(["store_id" => auth()?->user()?->store?->id || auth()?->user()?->store_id]);
        if (!$this->min) $this->merge(["min" => 0]);
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(BaseResponse::error("Kesalahan dalam validasi!", $validator->errors()));
    }
}
