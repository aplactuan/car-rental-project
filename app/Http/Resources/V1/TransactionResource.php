<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * JSON:API resource: type, id, attributes, relationships (linkage).
     * Related bookings go in top-level "included" when loaded.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'type' => 'transaction',
            'id' => $this->id,
            'attributes' => [
                'createdAt' => $this->created_at?->toIso8601String(),
                'userId' => $this->user_id,
            ],
            'relationships' => [
                'bookings' => [
                    'data' => $this->whenLoaded('bookings', fn () => $this->bookings->map(
                        fn ($b) => ['type' => 'booking', 'id' => $b->id]
                    )->values()->all(), []),
                ],
            ],
        ];

        return $data;
    }

    /**
     * Add included resources (bookings, and optionally their car/driver) for compound document.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        if (! $this->relationLoaded('bookings')) {
            return [];
        }

        $included = BookingResource::collection($this->bookings)->resolve();

        return ['included' => $included];
    }
}
