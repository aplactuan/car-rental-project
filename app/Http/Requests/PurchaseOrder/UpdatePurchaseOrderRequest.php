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
        ];
    }
}
