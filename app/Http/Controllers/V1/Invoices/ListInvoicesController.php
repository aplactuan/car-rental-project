<?php

namespace App\Http\Controllers\V1\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PurchaseOrderInvoiceResource;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListInvoicesController extends Controller
{
    public function __construct(protected InvoiceRepositoryInterface $invoiceRepository) {}

    public function __invoke(PurchaseOrder $purchaseOrder): AnonymousResourceCollection
    {
        return PurchaseOrderInvoiceResource::collection(
            $this->invoiceRepository->listForPurchaseOrder($purchaseOrder)
        );
    }
}
