<?php

namespace App\Repositories\Contracts;

use App\Models\Bill;

interface BillRepositoryInterface
{
    public function findByTransaction(string $transactionId): Bill;

    /**
     * @param  array{transaction_id: string, amount: int, notes?: string|null, due_at?: string|null}  $data
     */
    public function create(array $data): Bill;

    public function update(Bill $bill, array $data): Bill;
}
