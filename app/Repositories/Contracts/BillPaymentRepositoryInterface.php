<?php

namespace App\Repositories\Contracts;

use App\Models\Bill;
use App\Models\BillPayment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface BillPaymentRepositoryInterface
{
    /**
     * @return Collection<int, BillPayment>
     */
    public function listForBill(Bill $bill): Collection;

    /**
     * @param  array{amount: int, method: string, reference_number: string, notes?: string|null}  $data
     */
    public function create(Bill $bill, array $data, ?UploadedFile $proofImage = null): BillPayment;

    public function delete(BillPayment $payment): void;
}
