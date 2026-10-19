<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecentInvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'purchase-order-invoice',
            'id' => $this->id,
            'attributes' => [
                'invoiceNumber' => $this->invoice_number,
                'status' => $this->status->value,
                'amount' => (int) ($this->trip_reports_sum_amount ?? 0),
                'billedAt' => $this->billed_at?->toIso8601String(),
                'paidAt' => $this->paid_at?->toIso8601String(),
            ],
            'relationships' => [
                'purchaseOrder' => [
                    'data' => [
                        'type' => 'purchase-order',
                        'id' => $this->purchase_order_id,
                    ],
                ],
            ],
        ];
    }
}
