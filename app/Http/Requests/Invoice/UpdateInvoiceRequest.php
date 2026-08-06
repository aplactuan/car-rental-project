<?php

namespace App\Http\Requests\Invoice;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
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
        /** @var Invoice $invoice */
        $invoice = $this->route('invoice');

        return [
            'invoice_number' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('invoices', 'invoice_number')->ignore($invoice->id),
            ],
            'lddap_adap_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'note' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::enum(InvoiceStatus::class)],
            'remove_payment_receipt' => ['sometimes', 'boolean'],
            'remove_disbursement_voucher' => ['sometimes', 'boolean'],
            'payment_receipt' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
                'prohibited_if:remove_payment_receipt,true',
            ],
            'disbursement_voucher' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
                'prohibited_if:remove_disbursement_voucher,true',
            ],
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
            'payment_receipt.prohibited_if' => 'Do not upload a payment receipt when removing it.',
            'disbursement_voucher.mimes' => 'The disbursement voucher must be an image or PDF.',
            'disbursement_voucher.max' => 'The disbursement voucher must not exceed 10MB.',
            'disbursement_voucher.prohibited_if' => 'Do not upload a disbursement voucher when removing it.',
        ];
    }
}
