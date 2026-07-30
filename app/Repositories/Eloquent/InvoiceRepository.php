<?php

namespace App\Repositories\Eloquent;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function __construct(protected Invoice $model) {}

    public function listForPurchaseOrder(PurchaseOrder $purchaseOrder): Collection
    {
        return $this->model->newQuery()
            ->with('media')
            ->where('purchase_order_id', $purchaseOrder->id)
            ->latest()
            ->get();
    }

    public function findForPurchaseOrder(PurchaseOrder $purchaseOrder, Invoice $invoice): Invoice
    {
        if ($invoice->purchase_order_id !== $purchaseOrder->id) {
            abort(404);
        }

        return $invoice->load('media');
    }

    public function create(
        PurchaseOrder $purchaseOrder,
        array $data,
        ?UploadedFile $paymentReceipt = null,
        ?UploadedFile $disbursementVoucher = null
    ): Invoice {
        return DB::transaction(function () use ($purchaseOrder, $data, $paymentReceipt, $disbursementVoucher): Invoice {
            $invoice = $purchaseOrder->invoices()->create($data);

            if ($paymentReceipt !== null) {
                $invoice->addMedia($paymentReceipt)
                    ->toMediaCollection(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION);
            }

            if ($disbursementVoucher !== null) {
                $invoice->addMedia($disbursementVoucher)
                    ->toMediaCollection(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION);
            }

            return $invoice->fresh('media');
        });
    }

    public function update(
        PurchaseOrder $purchaseOrder,
        Invoice $invoice,
        array $data,
        ?UploadedFile $paymentReceipt = null,
        ?UploadedFile $disbursementVoucher = null,
        bool $removePaymentReceipt = false,
        bool $removeDisbursementVoucher = false
    ): Invoice {
        $invoice = $this->findForPurchaseOrder($purchaseOrder, $invoice);

        return DB::transaction(function () use ($invoice, $data, $paymentReceipt, $disbursementVoucher, $removePaymentReceipt, $removeDisbursementVoucher): Invoice {
            $invoice->update($data);

            if ($removePaymentReceipt) {
                $invoice->clearMediaCollection(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION);
            }

            if ($removeDisbursementVoucher) {
                $invoice->clearMediaCollection(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION);
            }

            if ($paymentReceipt !== null) {
                $invoice->addMedia($paymentReceipt)
                    ->toMediaCollection(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION);
            }

            if ($disbursementVoucher !== null) {
                $invoice->addMedia($disbursementVoucher)
                    ->toMediaCollection(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION);
            }

            return $invoice->fresh('media');
        });
    }
}
