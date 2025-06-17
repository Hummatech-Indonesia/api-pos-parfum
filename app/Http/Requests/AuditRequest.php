<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\BaseResponse;

class AuditRequest extends FormRequest
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
        if ($this->isMethod('POST')) {
            return [
                'name' => 'required|string',
                'description' => 'nullable|string',
                'outlet_id' => 'required|uuid',
                'date' => 'required|date',
                'status' => 'sometimes|string|in:pending,approved,rejected',
                'reason' => 'required_if:status,rejected|string|nullable',
                'products' => 'required|array|min:1',
                'products.*.product_detail_id' => 'required|uuid',
                'products.*.audit_stock' => 'required|integer|min:0',
                'products.*.unit_id' => 'required|uuid',
            ];
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return [
                'status' => 'sometimes|string|in:pending,approved,rejected',
                'reason' => 'required_if:status,rejected|string|nullable',
                'products' => 'nullable|array|min:1',
                'products.*.product_detail_id' => 'nullable|uuid',
                'products.*.audit_stock' => 'nullable|integer|min:0',
                'products.*.unit_id' => 'nullable|uuid',
            ];
        }

        return [];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama tidak boleh lebih dari 255 karakter.',
            'description.required' => 'Deskripsi wajib diisi.',
            'product_detail_id.required' => 'ID detail produk wajib diisi.',
            'product_detail_id.uuid' => 'ID detail produk harus berupa UUID yang valid.',
            'product_detail_id.exists' => 'ID detail produk tidak ditemukan dalam database.',
            'old_stock.numeric' => 'Stok lama harus berupa angka.',
            'audit_stock.required' => 'Stok audit wajib diisi.',
            'audit_stock.numeric' => 'Stok audit harus berupa angka.',
            'unit_id.uuid' => 'ID satuan harus berupa UUID yang valid.',
            'unit_id.exists' => 'ID satuan tidak ditemukan dalam database.',
            'status.in' => 'Status hanya boleh bernilai: pending, approved, atau rejected.',
            'reason.required_if' => 'Alasan wajib diisi jika status adalah rejected.',
            'reason.string' => 'Alasan harus berupa teks.',
            'date.required' => 'Tanggal wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
        ];
    }


    protected function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(BaseResponse::Error("Kesalahan dalam validasi", $validator->errors()));
    }
}
