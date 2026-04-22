<?php

namespace App\Repositories\Eloquent;

use App\Models\Bill;
use App\Repositories\Contracts\BillRepositoryInterface;
use Illuminate\Support\Facades\DB;

class BillRepository implements BillRepositoryInterface
{
    public function __construct(protected Bill $model) {}

    public function findByTransaction(string $transactionId): Bill
    {
        return $this->model->newQuery()
            ->where('transaction_id', $transactionId)
            ->firstOrFail();
    }

    public function create(array $data): Bill
    {
        return DB::transaction(function () use ($data): Bill {
            $bill = $this->model->create([
                'transaction_id' => $data['transaction_id'],
                'bill_number' => $this->nextBillNumber(),
                'amount' => $data['amount'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'due_at' => $data['due_at'] ?? null,
            ]);

            return $bill->fresh();
        });
    }

    public function update(Bill $bill, array $data): Bill
    {
        $bill->update($data);

        return $bill->fresh();
    }

    private function nextBillNumber(): string
    {
        $prefix = now()->format('Ymd');

        $latestTodayCount = $this->model->newQuery()
            ->whereDate('created_at', now()->toDateString())
            ->lockForUpdate()
            ->count();

        $sequence = str_pad((string) ($latestTodayCount + 1), 4, '0', STR_PAD_LEFT);

        return "INV-{$prefix}-{$sequence}";
    }
}
