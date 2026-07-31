<?php

namespace App\Http\Resources\V1;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'purchase-order',
            'id' => $this->id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'attributes' => [
                'createdAt' => $this->created_at?->toIso8601String(),
                'poNumber' => $this->po_number,
                'date' => $this->date?->toDateString(),
                'amount' => $this->amount,
                'requestPerson' => $this->request_person,
                'description' => $this->description,
                'status' => $this->status->value,
                'customerId' => $this->customer_id,
                'attachments' => $this->attachmentsData(),
            ],
            'relationships' => [
                'customer' => [
                    'data' => $this->customerRelationshipData(),
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{id: string, fileName: string, mimeType: string|null, size: int, url: string}>
     */
    private function attachmentsData(): array
    {
        return $this->getMedia(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION)
            ->map(fn ($media) => [
                'id' => $media->uuid,
                'fileName' => $media->file_name,
                'mimeType' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
            ])
            ->values()
            ->all();
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
