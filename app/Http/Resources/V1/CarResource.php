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
            'createdAt' => $this->created_at?->toIso8601String(),
            'attributes' => [
                'createdAt' => $this->created_at?->toIso8601String(),
                'type' => $this->type,
                'door' => $this->door,
                'seats' => $this->seats,
                'year' => $this->year,
                'color' => $this->color,
                'make' => $this->make,
                'model' => $this->model,
                'plateNumber' => $this->plate_number,
            ],
        ];
    }
}
