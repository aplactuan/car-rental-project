<?php

namespace App\Http\Controllers\V1\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\AddPurchaseOrderRequest;
use App\Http\Resources\V1\PurchaseOrderResource;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;

class AddPurchaseOrderController extends Controller
{
    public function __construct(protected PurchaseOrderRepositoryInterface $purchaseOrder) {}

    public function __invoke(AddPurchaseOrderRequest $request)
    {
        $data = $request->validated();
        unset($data['attachments']);

        $attachments = $request->file('attachments', []);
        if (! is_array($attachments)) {
            $attachments = $attachments ? [$attachments] : [];
        }

        $purchaseOrder = $this->purchaseOrder->create($data, $attachments);

        return (new PurchaseOrderResource($purchaseOrder))
            ->response()
            ->setStatusCode(201);
    }
}
