<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $cash_received_total
 * @property-read int $accounts_receivable_total
 */
class BillingSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'billingSummary',
            'id' => 'summary',
            'attributes' => [
                'cashReceivedTotal' => $this->resource['cash_received_total'],
                'accountsReceivableTotal' => $this->resource['accounts_receivable_total'],
            ],
        ];
    }
}
