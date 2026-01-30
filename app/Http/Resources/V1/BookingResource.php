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
                'startDate' => $this->start_date?->format('Y-m-d'),
                'endDate' => $this->end_date?->format('Y-m-d'),
            ],
        ];

        $data['relationships'] = [
            'car' => ['data' => ['type' => 'car', 'id' => $this->car_id]],
            'driver' => ['data' => ['type' => 'driver', 'id' => $this->driver_id]],
        ];

        return $data;
    }
}
