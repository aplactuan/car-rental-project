<?php

namespace App\Http\Resources\V1;

use App\Models\BillPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'bill-payment',
            'id' => $this->id,
            'attributes' => [
                'amount' => $this->amount,
                'method' => $this->method,
                'referenceNumber' => $this->reference_number,
                'notes' => $this->notes,
                'proofImageUrl' => $this->getFirstMediaUrl(BillPayment::PROOF_MEDIA_COLLECTION) ?: null,
                'paidAt' => $this->paid_at?->toIso8601String(),
                'createdAt' => $this->created_at?->toIso8601String(),
            ],
            'relationships' => [
                'bill' => [
                    'data' => [
                        'type' => 'bill',
                        'id' => $this->bill_id,
                    ],
                ],
            ],
        ];
    }
}
