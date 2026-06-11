<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $total_paid
 * @property-read int $total_unpaid
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
                'totalPaid' => $this->resource['total_paid'],
                'totalUnpaid' => $this->resource['total_unpaid'],
            ],
        ];
    }
}
