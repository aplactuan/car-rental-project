<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
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
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $this->route('purchaseOrder');

        return [
            'customer_id' => ['sometimes', 'nullable', 'uuid', 'exists:customers,id'],
            'po_number' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('purchase_orders', 'po_number')->ignore($purchaseOrder->id),
            ],
            'date' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'integer', 'min:0'],
            'request_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:10240'],
            'remove_attachment_ids' => ['sometimes', 'array'],
            'remove_attachment_ids.*' => [
                'uuid',
                Rule::exists('media', 'uuid')->where(function ($query) use ($purchaseOrder): void {
                    $query->where('model_type', $purchaseOrder->getMorphClass())
                        ->where('model_id', $purchaseOrder->id)
                        ->where('collection_name', PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION);
                }),
            ],
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
            'attachments.*.mimes' => 'Each attachment must be an image, document, or PDF.',
            'attachments.*.max' => 'Each attachment must not exceed 10MB.',
            'remove_attachment_ids.*.uuid' => 'Each attachment id to remove must be a valid UUID.',
            'remove_attachment_ids.*.exists' => 'One or more attachments to remove were not found.',
        ];
    }
}
