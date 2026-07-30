<?php

namespace App\Repositories\Contracts;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use Illuminate\Http\UploadedFile;

interface InvoiceRepositoryInterface
{
    /**
     * @param  array{invoice_number: string, lddap_adap_no: string, note?: string|null}  $data
     */
    public function create(
        PurchaseOrder $purchaseOrder,
        array $data,
        ?UploadedFile $paymentReceipt = null,
        ?UploadedFile $disbursementVoucher = null
    ): Invoice;
}
