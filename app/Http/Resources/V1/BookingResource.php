<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * JSON:API resource: type, id, attributes, optional relationships (linkage only).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'type' => 'booking',
            'id' => $this->id,
            'attributes' => [
                'note' => $this->note,
                'price' => $this->price,
                'startDate' => $this->start_date?->format('Y-m-d H:i:s'),
                'endDate' => $this->end_date?->format('Y-m-d H:i:s'),
            ],
        ];

        $data['relationships'] = [
            'car' => [
                'data' => array_merge(
                    ['type' => 'car', 'id' => $this->car_id],
                    $this->when($this->relationLoaded('car') && $this->car, [
                        'attributes' => [
                            'make' => $this->car->make,
                            'model' => $this->car->model,
                            'plateNumber' => $this->car->plate_number,
                        ],
                    ])
                ),
            ],
            'driver' => [
                'data' => array_merge(
                    ['type' => 'driver', 'id' => $this->driver_id],
                    $this->when($this->relationLoaded('driver') && $this->driver, [
                        'attributes' => [
                            'firstName' => $this->driver->first_name,
                            'lastName' => $this->driver->last_name,
                        ],
                    ])
                ),
            ],
            'transaction' => [
                'data' => array_merge(
                    ['type' => 'transaction', 'id' => $this->transaction_id],
                    $this->when(
                        $this->relationLoaded('transaction') && $this->transaction,
                        [
                            'attributes' => [
                                'name' => $this->transaction->name,
                                'userId' => $this->transaction->user_id,
                                'customerId' => $this->transaction->customer_id,
                                'createdAt' => $this->transaction->created_at?->toIso8601String(),
                            ],
                        ],
                        []
                    )
                ),
            ],
        ];

        return $data;
    }
}
