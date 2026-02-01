<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    /**
     * JSON:API resource: type, id, attributes only.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'driver',
            'id' => $this->id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'attributes' => [
                'createdAt' => $this->created_at?->toIso8601String(),
                'firstName' => $this->first_name,
                'lastName' => $this->last_name,
                'licenseNumber' => $this->license_number,
                'licenseExpiryDate' => $this->license_expiry_date?->format('Y-m-d'),
                'address' => $this->address,
                'phoneNumber' => $this->phone_number,
            ],
        ];
    }
}

