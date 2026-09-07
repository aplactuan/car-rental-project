<?php

namespace App\Repositories\Eloquent;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Support\Media\MediaUploader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function __construct(
        protected Invoice $model,
        protected MediaUploader $mediaUploader
    ) {}

    public function listForPurchaseOrder(PurchaseOrder $purchaseOrder): Collection
    {
        return $this->model->newQuery()
            ->with('media')
            ->withCount([
                'tripReports as trip_reports_count' => fn ($query) => $query->where('purchase_order_id', $purchaseOrder->id),
            ])
            ->withSum([
                'tripReports as trip_reports_sum_amount' => fn ($query) => $query->where('purchase_order_id', $purchaseOrder->id),
            ], 'amount')
            ->where('purchase_order_id', $purchaseOrder->id)
            ->latest()
            ->get();
    }

    public function findForPurchaseOrder(PurchaseOrder $purchaseOrder, Invoice $invoice): Invoice
    {
        if ($invoice->purchase_order_id !== $purchaseOrder->id) {
            abort(404);
        }

        return $this->loadTripReportAggregates($invoice->load('media'));
    }

    public function create(
        PurchaseOrder $purchaseOrder,
        array $data,
        ?UploadedFile $paymentReceipt = null,
        ?UploadedFile $disbursementVoucher = null,
        ?UploadedFile $invoicePicture = null
    ): Invoice {
        return DB::transaction(function () use ($purchaseOrder, $data, $paymentReceipt, $disbursementVoucher, $invoicePicture): Invoice {
            $data['billed_at'] ??= now();

            if (($data['status'] ?? InvoiceStatus::Unpaid->value) === InvoiceStatus::Paid->value) {
                $data['paid_at'] = now();
            }

            $invoice = $purchaseOrder->invoices()->create($data);

            if ($paymentReceipt !== null) {
                $this->mediaUploader->add(
                    $invoice,
                    $paymentReceipt,
                    Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION
                );
            }

            if ($disbursementVoucher !== null) {
                $this->mediaUploader->add(
                    $invoice,
                    $disbursementVoucher,
                    Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION
                );
            }

            if ($invoicePicture !== null) {
                $this->mediaUploader->add(
                    $invoice,
                    $invoicePicture,
                    Invoice::INVOICE_PICTURE_MEDIA_COLLECTION
                );
            }

            return $this->loadTripReportAggregates($invoice->fresh('media'));
        });
    }

    public function update(
        PurchaseOrder $purchaseOrder,
        Invoice $invoice,
        array $data,
        ?UploadedFile $paymentReceipt = null,
        ?UploadedFile $disbursementVoucher = null,
        ?UploadedFile $invoicePicture = null,
        bool $removePaymentReceipt = false,
        bool $removeDisbursementVoucher = false,
        bool $removeInvoicePicture = false
    ): Invoice {
        $invoice = $this->findForPurchaseOrder($purchaseOrder, $invoice);

        return DB::transaction(function () use ($invoice, $data, $paymentReceipt, $disbursementVoucher, $invoicePicture, $removePaymentReceipt, $removeDisbursementVoucher, $removeInvoicePicture): Invoice {
            if (array_key_exists('status', $data)) {
                if ($data['status'] === InvoiceStatus::Paid->value && $invoice->status !== InvoiceStatus::Paid) {
                    $data['paid_at'] = now();
                }

                if ($data['status'] === InvoiceStatus::Unpaid->value) {
                    $data['paid_at'] = null;
                }
            }

            $invoice->update($data);

            if ($removePaymentReceipt) {
                $invoice->clearMediaCollection(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION);
            }

            if ($removeDisbursementVoucher) {
                $invoice->clearMediaCollection(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION);
            }

            if ($removeInvoicePicture) {
                $invoice->clearMediaCollection(Invoice::INVOICE_PICTURE_MEDIA_COLLECTION);
            }

            if ($paymentReceipt !== null) {
                $this->mediaUploader->add(
                    $invoice,
                    $paymentReceipt,
                    Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION
                );
            }

            if ($disbursementVoucher !== null) {
                $this->mediaUploader->add(
                    $invoice,
                    $disbursementVoucher,
                    Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION
                );
            }

            if ($invoicePicture !== null) {
                $this->mediaUploader->add(
                    $invoice,
                    $invoicePicture,
                    Invoice::INVOICE_PICTURE_MEDIA_COLLECTION
                );
            }

            return $this->loadTripReportAggregates($invoice->fresh('media'));
        });
    }

    public function delete(PurchaseOrder $purchaseOrder, Invoice $invoice): void
    {
        $invoice = $this->findForPurchaseOrder($purchaseOrder, $invoice);

        $invoice->delete();
    }

    public function attachTripReports(
        PurchaseOrder $purchaseOrder,
        Invoice $invoice,
        array $tripReportIds
    ): Collection {
        $invoice = $this->findForPurchaseOrder($purchaseOrder, $invoice);

        return DB::transaction(function () use ($purchaseOrder, $invoice, $tripReportIds): Collection {
            TripReport::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->whereIn('id', $tripReportIds)
                ->update(['invoice_id' => $invoice->id]);

            return TripReport::query()
                ->with('media')
                ->where('purchase_order_id', $purchaseOrder->id)
                ->whereIn('id', $tripReportIds)
                ->get();
        });
    }

    public function detachTripReports(
        PurchaseOrder $purchaseOrder,
        Invoice $invoice,
        array $tripReportIds
    ): Collection {
        $invoice = $this->findForPurchaseOrder($purchaseOrder, $invoice);

        return DB::transaction(function () use ($purchaseOrder, $invoice, $tripReportIds): Collection {
            TripReport::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->where('invoice_id', $invoice->id)
                ->whereIn('id', $tripReportIds)
                ->update(['invoice_id' => null]);

            return TripReport::query()
                ->with('media')
                ->where('purchase_order_id', $purchaseOrder->id)
                ->whereIn('id', $tripReportIds)
                ->get();
        });
    }

    private function loadTripReportAggregates(Invoice $invoice): Invoice
    {
        return $invoice
            ->loadCount([
                'tripReports as trip_reports_count' => fn ($query) => $query->where('purchase_order_id', $invoice->purchase_order_id),
            ])
            ->loadSum([
                'tripReports as trip_reports_sum_amount' => fn ($query) => $query->where('purchase_order_id', $invoice->purchase_order_id),
            ], 'amount');
    }
}
