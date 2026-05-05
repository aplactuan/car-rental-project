<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'user',
            'id' => $this->id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'attributes' => [
                'name' => $this->name,
                'email' => $this->email,
                'createdAt' => $this->created_at?->toIso8601String(),
            ],
        ];
    }
}
