<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecentTripReportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'trip-report',
            'id' => $this->id,
            'attributes' => [
                'tripReportNo' => $this->trip_report_no,
                'driver' => $this->driver,
                'reportDate' => $this->report_date?->toDateString(),
                'tripStart' => $this->trip_start?->toDateString(),
                'tripEnd' => $this->trip_end?->toDateString(),
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
