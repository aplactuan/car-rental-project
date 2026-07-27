<?php

namespace App\Http\Controllers\V1\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Http\JsonResponse;

class DeletePurchaseOrderController extends Controller
{
    public function __construct(protected PurchaseOrderRepositoryInterface $purchaseOrder) {}

    public function __invoke(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->purchaseOrder->delete($purchaseOrder->id);

        return response()->json(null, 204);
    }
}
