<?php

namespace App\Http\Controllers\V1\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\CreateInvoiceRequest;
use App\Http\Resources\V1\PurchaseOrderInvoiceResource;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Http\JsonResponse;

class AddInvoiceController extends Controller
{
    public function __construct(protected InvoiceRepositoryInterface $invoiceRepository) {}

    public function __invoke(CreateInvoiceRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $data = $request->validated();
        unset($data['payment_receipt'], $data['disbursement_voucher']);

        $invoice = $this->invoiceRepository->create(
            $purchaseOrder,
            $data,
            $request->file('payment_receipt'),
            $request->file('disbursement_voucher')
        );

        return (new PurchaseOrderInvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }
}
