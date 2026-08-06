<?php

namespace App\Http\Resources\V1;

use App\Models\TripReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'trip-report',
            'id' => $this->id,
            'attributes' => [
                'tripReportNo' => $this->trip_report_no,
                'reportDate' => $this->report_date?->toDateString(),
                'tripStart' => $this->trip_start?->toDateString(),
                'tripEnd' => $this->trip_end?->toDateString(),
                'driver' => $this->driver,
                'destinations' => $this->destinations,
                'amount' => $this->amount,
                'tripReportImageUrl' => $this->getFirstMediaUrl(TripReport::TRIP_REPORT_IMAGE_MEDIA_COLLECTION) ?: null,
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
                'invoice' => [
                    'data' => $this->invoice_id === null ? null : [
                        'type' => 'purchase-order-invoice',
                        'id' => $this->invoice_id,
                    ],
                ],
            ],
        ];
    }
}
