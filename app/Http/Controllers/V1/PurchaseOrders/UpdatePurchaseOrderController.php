<?php

namespace App\Http\Controllers\V1\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Http\Resources\V1\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Traits\ApiResponses;

class UpdatePurchaseOrderController extends Controller
{
    use ApiResponses;

    public function __construct(protected PurchaseOrderRepositoryInterface $purchaseOrder) {}

    public function __invoke(PurchaseOrder $purchaseOrder, UpdatePurchaseOrderRequest $request)
    {
        $purchaseOrder = $this->purchaseOrder->find($purchaseOrder->id);

        if (! $purchaseOrder) {
            return $this->error('Purchase order not found', 404);
        }

        $data = $request->validated();
        $removeAttachmentIds = $data['remove_attachment_ids'] ?? [];
        unset($data['attachments'], $data['remove_attachment_ids']);

        $attachments = $request->file('attachments', []);
        if (! is_array($attachments)) {
            $attachments = $attachments ? [$attachments] : [];
        }

        $purchaseOrder = $this->purchaseOrder->update(
            $purchaseOrder->id,
            $data,
            $attachments,
            $removeAttachmentIds
        );

        return new PurchaseOrderResource($purchaseOrder);
    }
}
