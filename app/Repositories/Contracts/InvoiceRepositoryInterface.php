<?php

namespace App\Repositories\Contracts;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface InvoiceRepositoryInterface
{
    /**
     * @return Collection<int, Invoice>
     */
    public function listForPurchaseOrder(PurchaseOrder $purchaseOrder): Collection;

    public function findForPurchaseOrder(PurchaseOrder $purchaseOrder, Invoice $invoice): Invoice;

    /**
     * @param  array{invoice_number: string, lddap_adap_no: string, note?: string|null}  $data
     */
    public function create(
        PurchaseOrder $purchaseOrder,
        array $data,
        ?UploadedFile $paymentReceipt = null,
        ?UploadedFile $disbursementVoucher = null
    ): Invoice;

    /**
     * @param  array{invoice_number?: string, lddap_adap_no?: string, note?: string|null}  $data
     */
    public function update(
        PurchaseOrder $purchaseOrder,
        Invoice $invoice,
        array $data,
        ?UploadedFile $paymentReceipt = null,
        ?UploadedFile $disbursementVoucher = null,
        bool $removePaymentReceipt = false,
        bool $removeDisbursementVoucher = false
    ): Invoice;

    public function delete(PurchaseOrder $purchaseOrder, Invoice $invoice): void;

    /**
     * @param  list<string>  $tripReportIds
     * @return Collection<int, TripReport>
     */
    public function attachTripReports(
        PurchaseOrder $purchaseOrder,
        Invoice $invoice,
        array $tripReportIds
    ): Collection;

    /**
     * @param  list<string>  $tripReportIds
     * @return Collection<int, TripReport>
     */
    public function detachTripReports(
        PurchaseOrder $purchaseOrder,
        Invoice $invoice,
        array $tripReportIds
    ): Collection;
}
