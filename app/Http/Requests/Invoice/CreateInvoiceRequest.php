<?php

namespace App\Http\Requests\Invoice;

use App\Enums\InvoiceStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_number' => ['required', 'string', 'max:255', 'unique:invoices,invoice_number'],
            'lddap_adap_no' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(InvoiceStatus::class)],
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'disbursement_voucher' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.enum' => 'The status must be either unpaid or paid.',
            'payment_receipt.mimes' => 'The payment receipt must be an image or PDF.',
            'payment_receipt.max' => 'The payment receipt must not exceed 10MB.',
            'disbursement_voucher.mimes' => 'The disbursement voucher must be an image or PDF.',
            'disbursement_voucher.max' => 'The disbursement voucher must not exceed 10MB.',
        ];
    }
}
