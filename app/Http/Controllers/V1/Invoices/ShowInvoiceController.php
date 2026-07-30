<?php

namespace App\Http\Controllers\V1\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PurchaseOrderInvoiceResource;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\InvoiceRepositoryInterface;

class ShowInvoiceController extends Controller
{
    public function __construct(protected InvoiceRepositoryInterface $invoiceRepository) {}

    public function __invoke(PurchaseOrder $purchaseOrder, Invoice $invoice): PurchaseOrderInvoiceResource
    {
        return new PurchaseOrderInvoiceResource(
            $this->invoiceRepository->findForPurchaseOrder($purchaseOrder, $invoice)
        );
    }
}
