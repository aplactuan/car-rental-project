<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverNameResource extends JsonResource
{
    /**
     * JSON:API resource with name attributes only.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'driver',
            'id' => $this->id,
            'attributes' => [
                'firstName' => $this->first_name,
                'lastName' => $this->last_name,
            ],
        ];
    }
}
