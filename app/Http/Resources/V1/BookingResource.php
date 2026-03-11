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
        ];

        return $data;
    }
}
