<?php

namespace App\Http\Requests\Transaction;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class ShiftUserRequest extends FormRequest
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
            'outlet_id' => 'required|exists:outlets,id',
            'start_price' => 'sometimes|min:0',
            'end_price' => 'required|min:1',
            'date' => 'nullable'
        ];
    }

    public function messages(): array
    {
        return [
            'outlet_id.required' => 'Anda tidak terdaftar dalam outlet!',
            'outlet_id.exists' => 'Anda tidak terdaftar dalam outlet!',
            'start_price.sometimes' => 'Jumlah uang mulai harus di isi!',
            'start_price.min' => 'Jumlah uang minimal 0!',
            'end_price.sometimes' => 'Jumlah uang selesai harus di isi!',
            'end_price.min' => 'Jumlah uang selesai minimal 1!',
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }

    public function prepareForValidation()
    {
        if(!$this->outlet_id) $this->merge(["outlet_id" => auth()->user()?->outlet_id]);
        if(!$this->date) $this->merge(["date" => now()]);
    }
}
