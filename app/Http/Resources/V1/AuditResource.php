<?php

namespace App\Http\Resources\V1;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OwenIt\Auditing\Models\Audit;

/** @mixin Audit */
class AuditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'audit',
            'id' => (string) $this->id,
            'attributes' => [
                'event' => $this->event,
                'auditableType' => $this->auditableType(),
                'auditableId' => $this->auditable_id,
                'customerId' => $this->customer_id,
                'oldValues' => $this->old_values ?? [],
                'newValues' => $this->new_values ?? [],
                'changedFields' => array_values(array_unique(array_merge(
                    array_keys($this->old_values ?? []),
                    array_keys($this->new_values ?? [])
                ))),
                'url' => $this->url,
                'ipAddress' => $this->ip_address,
                'userAgent' => $this->user_agent,
                'createdAt' => $this->created_at?->toIso8601String(),
            ],
            'relationships' => [
                'user' => [
                    'data' => $this->user ? [
                        'type' => 'user',
                        'id' => (string) $this->user->getAuthIdentifier(),
                        'attributes' => [
                            'name' => $this->user->name,
                            'email' => $this->user->email,
                        ],
                    ] : null,
                ],
            ],
        ];
    }

    private function auditableType(): string
    {
        return match ($this->auditable_type) {
            TripReport::class => 'trip-report',
            PurchaseOrder::class => 'purchase-order',
            Invoice::class => 'invoice',
            default => $this->auditable_type,
        };
    }
}
