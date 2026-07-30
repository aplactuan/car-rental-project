<?php

namespace App\Http\Requests\Invoice;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachTripReportsToInvoiceRequest extends FormRequest
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
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $this->route('purchaseOrder');

        return [
            'trip_report_ids' => ['required', 'array', 'min:1'],
            'trip_report_ids.*' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('trip_reports', 'id')->where(
                    fn ($query) => $query->where('purchase_order_id', $purchaseOrder->id)
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trip_report_ids.required' => 'At least one trip report must be selected.',
            'trip_report_ids.min' => 'At least one trip report must be selected.',
            'trip_report_ids.*.exists' => 'Each trip report must belong to this purchase order.',
            'trip_report_ids.*.distinct' => 'Duplicate trip report IDs are not allowed.',
        ];
    }
}
