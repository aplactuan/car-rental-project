<?php

namespace App\Repositories\Eloquent;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function __construct(protected Invoice $model) {}

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
}
