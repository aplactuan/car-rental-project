<?php

namespace App\Http\Controllers\V1\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Http\JsonResponse;

class DeleteInvoiceController extends Controller
{
    public function __construct(protected InvoiceRepositoryInterface $invoiceRepository) {}

    public function __invoke(PurchaseOrder $purchaseOrder, Invoice $invoice): JsonResponse
    {
        $this->invoiceRepository->delete($purchaseOrder, $invoice);

        return response()->json(null, 204);
    }
}
