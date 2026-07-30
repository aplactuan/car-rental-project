<?php

namespace App\Http\Resources\V1;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderInvoiceResource extends JsonResource
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
                'lddapAdapNo' => $this->lddap_adap_no,
                'note' => $this->note,
                'paymentReceiptUrl' => $this->getFirstMediaUrl(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION) ?: null,
                'disbursementVoucherUrl' => $this->getFirstMediaUrl(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION) ?: null,
                'createdAt' => $this->created_at?->toIso8601String(),
                'updatedAt' => $this->updated_at?->toIso8601String(),
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
