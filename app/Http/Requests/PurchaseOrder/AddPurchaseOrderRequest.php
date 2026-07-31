<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'po_number' => ['required', 'string', 'max:255', 'unique:purchase_orders,po_number'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:0'],
            'request_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::enum(PurchaseOrderStatus::class)],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.uuid' => 'The selected customer must be a valid UUID.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'po_number.unique' => 'The purchase order number has already been taken.',
            'status.enum' => 'The status must be either pending or ok.',
            'attachments.*.mimes' => 'Each attachment must be an image, document, or PDF.',
            'attachments.*.max' => 'Each attachment must not exceed 10MB.',
        ];
    }
}
