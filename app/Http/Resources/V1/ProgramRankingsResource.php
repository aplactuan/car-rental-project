<?php

namespace App\Http\Resources\V1;

use App\Models\Program;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramRankingsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'programRankings',
            'id' => 'rankings',
            'attributes' => [
                'topPrograms' => $this->programs(
                    $this->resource['top_programs'],
                    'purchase_order_total',
                    'purchaseOrderTotal'
                ),
                'topBilledPrograms' => $this->programs(
                    $this->resource['top_billed_programs'],
                    'invoice_amount_total',
                    'billedTotal'
                ),
                'topPaidPrograms' => $this->programs(
                    $this->resource['top_paid_programs'],
                    'invoice_amount_total',
                    'paidTotal'
                ),
            ],
        ];
    }

    /**
     * @param  Collection<int, Program>  $programs
     * @return list<array<string, int|string|null>>
     */
    private function programs(Collection $programs, string $sourceAmount, string $responseAmount): array
    {
        return $programs
            ->map(fn (Program $program): array => [
                'id' => $program->id,
                'name' => $program->name,
                'customerId' => $program->customer_id,
                $responseAmount => (int) $program->{$sourceAmount},
            ])
            ->values()
            ->all();
    }
}
