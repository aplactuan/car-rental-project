<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type'=> 'car',
            'id' => $this->id,
            'createdAt' => $this->created_at,
            'attributes' => [
                'make' => $this->make,
                'model' => $this->model,
                'year' => $this->year,
                'mileage' => $this->mileage,
                'type' => $this->type,
                'numberOfSeats' => $this->number_of_seats
            ]
        ];
    }
}
