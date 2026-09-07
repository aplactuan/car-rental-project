<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthReportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'monthReport',
            'id' => 'last-12-months',
            'attributes' => [
                'months' => collect($this->resource)
                    ->map(fn (array $month): array => [
                        'month' => $month['month'],
                        'totalBilled' => (int) $month['total_billed'],
                        'totalCollectible' => (int) $month['total_collectible'],
                        'totalPaid' => (int) $month['total_paid'],
                    ])
                    ->all(),
            ],
        ];
    }
}
