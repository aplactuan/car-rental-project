<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    /**
     * JSON:API resource: type, id, attributes only.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'car',
            'id' => $this->id,
            'attributes' => [
                'createdAt' => $this->created_at?->toIso8601String(),
                'make' => $this->make,
                'model' => $this->model,
                'year' => $this->year,
                'mileage' => $this->mileage,
                'vehicleType' => $this->type,
                'numberOfSeats' => $this->number_of_seats,
                'plateNumber' => $this->plate_number,
            ],
        ];
    }
}
