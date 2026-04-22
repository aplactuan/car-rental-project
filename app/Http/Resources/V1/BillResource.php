<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'bill',
            'id' => $this->id,
            'attributes' => [
                'billNumber' => $this->bill_number,
                'amount' => $this->amount,
                'status' => $this->status,
                'notes' => $this->notes,
                'issuedAt' => $this->issued_at?->toIso8601String(),
                'dueAt' => $this->due_at?->toIso8601String(),
                'paidAt' => $this->paid_at?->toIso8601String(),
                'createdAt' => $this->created_at?->toIso8601String(),
            ],
            'relationships' => [
                'transaction' => [
                    'data' => [
                        'type' => 'transaction',
                        'id' => $this->transaction_id,
                    ],
                ],
            ],
        ];
    }
}
