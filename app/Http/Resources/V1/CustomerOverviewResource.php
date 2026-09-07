<?php

namespace App\Http\Resources\V1;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOverviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'customerOverview',
            'id' => 'overview',
            'attributes' => [
                'totalPrograms' => (int) $this->resource['total_programs'],
                'balanceToCollect' => (int) $this->resource['balance_to_collect'],
                'topCustomers' => $this->resource['top_customers']
                    ->map(fn (Customer $customer): array => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'type' => $customer->type,
                        'purchaseOrderCount' => (int) $customer->purchase_orders_count,
                        'purchaseOrderTotal' => (int) ($customer->purchase_orders_sum_amount ?? 0),
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }
}
