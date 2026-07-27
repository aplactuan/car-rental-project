<?php

namespace App\Http\Controllers\V1\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\ListPurchaseOrdersRequest;
use App\Http\Resources\V1\PurchaseOrderResource;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;

class ListPurchaseOrdersController extends Controller
{
    public function __construct(protected PurchaseOrderRepositoryInterface $purchaseOrder) {}

    public function __invoke(ListPurchaseOrdersRequest $request)
    {
        $perPage = $request->integer('per_page', 15);
        $purchaseOrders = $this->purchaseOrder->paginate($perPage, $request->filters());

        return PurchaseOrderResource::collection($purchaseOrders);
    }
}
