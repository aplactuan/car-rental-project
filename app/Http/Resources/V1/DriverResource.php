<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'driver',
            'id' => $this->id,
            'createdAt' => $this->created_at,
            'attributes' => [
                'firstName' => $this->first_name,
                'lastName' => $this->last_name,
                'licenseNumber' => $this->license_number,
                'licenseExpiryDate' => $this->license_expiry_date,
                'address' => $this->address,
                'phoneNumber' => $this->phone_number,
            ],
        ];
    }
}

