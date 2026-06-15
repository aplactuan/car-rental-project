<?php

namespace App\Repositories\Eloquent;

use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Repositories\Contracts\BillPaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BillPaymentRepository implements BillPaymentRepositoryInterface
{
    public function __construct(protected BillPayment $model) {}

    public function listForBill(Bill $bill): Collection
    {
        return $this->model->newQuery()
            ->with('media')
            ->where('bill_id', $bill->id)
            ->orderByDesc('paid_at')
            ->get();
    }

    public function create(Bill $bill, array $data, ?UploadedFile $proofImage = null): BillPayment
    {
        return DB::transaction(function () use ($bill, $data, $proofImage): BillPayment {
            $payment = $this->model->create([
                'bill_id' => $bill->id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference_number' => $data['reference_number'],
                'notes' => $data['notes'] ?? null,
                'paid_at' => now(),
            ]);

            if ($proofImage !== null) {
                $payment->addMedia($proofImage)->toMediaCollection(BillPayment::PROOF_MEDIA_COLLECTION);
            }

            $this->recomputeBillStatus($bill->fresh());

            return $payment->fresh('media');
        });
    }

    public function delete(BillPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $bill = $payment->bill;

            $payment->delete();

            $this->recomputeBillStatus($bill->fresh());
        });
    }

    private function recomputeBillStatus(Bill $bill): void
    {
        $amountPaid = $bill->amountPaid();

        $status = match (true) {
            $amountPaid >= $bill->amount => BillStatus::Paid,
            $amountPaid > 0 => BillStatus::PartiallyPaid,
            default => BillStatus::Issued,
        };

        $bill->update([
            'status' => $status->value,
            'paid_at' => $status === BillStatus::Paid ? now() : null,
        ]);
    }
}
