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
            'desc' => 'sometimes',
            'minimum_purchase' => 'sometimes',
            'discount' => 'nullable|integer|min:0',
            'end_date' => 'sometimes|date',
            'start_date' => 'sometimes|date',
            // 'expired' => 'sometimes|after:today',
            'type' => 'sometimes|in:percentage,nominal',
            'percentage' => 'nullable|numeric|required_if:type,percentage|prohibited_if:type,nominal',
            'nominal' => 'nullable|numeric|required_if:type,nominal|prohibited_if:type,percentage',
            'is_member' => 'nullable|boolean',
            'active' => 'nullable|boolean',

        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama discount / voucher harus diisi!',
            'discount.required' => 'Jumlah discount harus diisi!',
            'expired.after' => 'Tenggat discount / voucher harus melebihi dari hari ini!',
            'type.in' => 'Tipe diskon harus berupa percentage atau nominal.',

            'percentage.required_if' => 'Field percentage wajib diisi jika tipe diskon adalah percentage.',
            'percentage.prohibited_if' => 'Field percentage tidak boleh diisi jika tipe diskon adalah nominal.',

            'nominal.required_if' => 'Field nominal wajib diisi jika tipe diskon adalah nominal.',
            'nominal.prohibited_if' => 'Field nominal tidak boleh diisi jika tipe diskon adalah percentage.',
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasNominal = $this->filled('nominal');
            $hasPercentage = $this->filled('percentage');

            if (!$hasNominal && !$hasPercentage) {
                $validator->errors()->add('nominal', 'Isi salah satu: nominal atau percentage.');
                $validator->errors()->add('percentage', 'Isi salah satu: nominal atau percentage.');
            }

            if ($hasNominal && $hasPercentage) {
                $validator->errors()->add('nominal', 'Hanya salah satu yang boleh diisi: nominal atau percentage.');
                $validator->errors()->add('percentage', 'Hanya salah satu yang boleh diisi: nominal atau percentage.');
            }
        });
    }
}
