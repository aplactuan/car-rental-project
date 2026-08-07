<?php

namespace App\Http\Controllers\V1\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Http\Resources\V1\PurchaseOrderInvoiceResource;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\InvoiceRepositoryInterface;

class UpdateInvoiceController extends Controller
{
    public function __construct(protected InvoiceRepositoryInterface $invoiceRepository) {}

    public function __invoke(
        UpdateInvoiceRequest $request,
        PurchaseOrder $purchaseOrder,
        Invoice $invoice,
    ): PurchaseOrderInvoiceResource {
        $data = $request->validated();
        unset(
            $data['payment_receipt'],
            $data['disbursement_voucher'],
            $data['invoice_picture'],
            $data['remove_payment_receipt'],
            $data['remove_disbursement_voucher'],
            $data['remove_invoice_picture']
        );

        $invoice = $this->invoiceRepository->update(
            $purchaseOrder,
            $invoice,
            $data,
            $request->file('payment_receipt'),
            $request->file('disbursement_voucher'),
            $request->file('invoice_picture'),
            $request->boolean('remove_payment_receipt'),
            $request->boolean('remove_disbursement_voucher'),
            $request->boolean('remove_invoice_picture')
        );

        return new PurchaseOrderInvoiceResource($invoice);
    }
}
