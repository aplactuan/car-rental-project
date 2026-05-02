<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarImportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'car-import',
            'id' => $this->id,
            'attributes' => [
                'status' => $this->status->value,
                'totalRows' => $this->total_rows,
                'importedCount' => $this->imported_count,
                'failedCount' => $this->failed_count,
                'failures' => $this->failures,
                'createdAt' => $this->created_at?->toIso8601String(),
                'updatedAt' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }
}
