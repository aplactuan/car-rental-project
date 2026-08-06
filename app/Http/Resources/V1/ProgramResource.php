<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'program',
            'id' => $this->id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'attributes' => [
                'createdAt' => $this->created_at?->toIso8601String(),
                'name' => $this->name,
                'description' => $this->description,
                'customerId' => $this->customer_id,
            ],
            'relationships' => [
                'customer' => [
                    'data' => $this->customerRelationshipData(),
                ],
            ],
        ];
    }

    /**
     * @return array{type: string, id: string, attributes?: array{name: string}}|null
     */
    private function customerRelationshipData(): ?array
    {
        if (! $this->customer_id) {
            return null;
        }

        $data = [
            'type' => 'customer',
            'id' => $this->customer_id,
        ];

        if ($this->relationLoaded('customer') && $this->customer) {
            $data['attributes'] = [
                'name' => $this->customer->name,
            ];
        }

        return $data;
    }
}
