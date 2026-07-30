<?php

namespace App\Http\Controllers\V1\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\AttachTripReportsToInvoiceRequest;
use App\Http\Resources\V1\TripReportResource;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttachTripReportsToInvoiceController extends Controller
{
    public function __construct(protected InvoiceRepositoryInterface $invoiceRepository) {}

    public function __invoke(
        AttachTripReportsToInvoiceRequest $request,
        PurchaseOrder $purchaseOrder,
        Invoice $invoice,
    ): AnonymousResourceCollection {
        $tripReports = $this->invoiceRepository->attachTripReports(
            $purchaseOrder,
            $invoice,
            $request->validated('trip_report_ids')
        );

        return TripReportResource::collection($tripReports);
    }
}
