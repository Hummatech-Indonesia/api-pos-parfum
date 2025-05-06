<?php

namespace App\Http\Requests\Transaction;

use App\Helpers\BaseResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class ShiftUserSyncRequest extends FormRequest
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
            'shift' => 'required|array',
            'shift.*.outlet_id' => 'required|exists:outlets,id',
            'shift.*.user_id' => 'required|exists:users,id',
            'shift.*.start_price' => 'sometimes|min:0',
            'shift.*.end_price' => 'required|min:1',
            'shift.*.date' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'shift.required' => 'Anda harus mengirimkan shift yang ingin disinkronisasi!',
            'shift.array' => 'Shift yang dikirimkan tidak valid',
            'shift.*.outlet_id.required' => 'User tidak terdaftar dalam outlet!',
            'shift.*.outlet_id.exists' => 'User tidak terdaftar dalam outlet!',
            'shift.*.user_id.required' => 'User tidak terdaftar dalam data!',
            'shift.*.user_id.exists' => 'User tidak terdaftar dalam data!',
            'shift.*.start_price.sometimes' => 'Jumlah uang mulai harus di isi!',
            'shift.*.start_price.min' => 'Jumlah uang minimal 0!',
            'shift.*.end_price.sometimes' => 'Jumlah uang selesai harus di isi!',
            'shift.*.end_price.min' => 'Jumlah uang selesai minimal 1!',
            'shift.*.date.required' => 'Tanggal shift harus di isi!',
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
