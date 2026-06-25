<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'trip-report',
            'id' => $this->id,
            'attributes' => [
                'reportDate' => $this->report_date?->format('Y-m-d'),
                'poNumber' => $this->po_number,
                'timeIn' => $this->time_in,
                'timeOut' => $this->time_out,
                'rate' => $this->rate,
                'odometerIn' => $this->odometer_in,
                'odometerOut' => $this->odometer_out,
                'fuelLiters' => $this->fuel_liters !== null ? (float) $this->fuel_liters : null,
                'fuelAmount' => $this->fuel_amount,
                'invoiceOrOrNumber' => $this->invoice_or_or_number,
                'collectionAmount' => $this->collection_amount,
                'percentage' => $this->percentage !== null ? (float) $this->percentage : null,
                'destinations' => $this->destinations ?? [],
                'driverIdSnapshot' => $this->driver_id_snapshot,
                'driverNameSnapshot' => $this->driver_name_snapshot,
                'carIdSnapshot' => $this->car_id_snapshot,
                'carMakeSnapshot' => $this->car_make_snapshot,
                'carModelSnapshot' => $this->car_model_snapshot,
                'carPlateNumberSnapshot' => $this->car_plate_number_snapshot,
                'customerIdSnapshot' => $this->customer_id_snapshot,
                'customerNameSnapshot' => $this->customer_name_snapshot,
                'transactionNameSnapshot' => $this->transaction_name_snapshot,
                'createdAt' => $this->created_at?->toIso8601String(),
                'updatedAt' => $this->updated_at?->toIso8601String(),
            ],
            'relationships' => [
                'booking' => [
                    'data' => [
                        'type' => 'booking',
                        'id' => $this->booking_id,
                    ],
                ],
            ],
        ];
    }
}
