<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * JSON:API resource: type, id, attributes, relationships (linkage).
     * Parent name is included only when the parent relation is loaded (single resources).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'customer',
            'id' => $this->id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'attributes' => [
                'createdAt' => $this->created_at?->toIso8601String(),
                'name' => $this->name,
                'type' => $this->type,
                'parentId' => $this->parent_id,
                'contactPerson' => $this->contact_person,
                'contactMobileNumber' => $this->contact_mobile_number,
                'contactEmail' => $this->contact_email,
            ],
            'relationships' => [
                'parent' => [
                    'data' => $this->parentRelationshipData(),
                ],
            ],
        ];
    }

    /**
     * @return array{type: string, id: string, attributes?: array{name: string}}|null
     */
    private function parentRelationshipData(): ?array
    {
        if (! $this->parent_id) {
            return null;
        }

        $data = [
            'type' => 'customer',
            'id' => $this->parent_id,
        ];

        if ($this->relationLoaded('parent') && $this->parent) {
            $data['attributes'] = [
                'name' => $this->parent->name,
            ];
        }

        return $data;
    }
}
