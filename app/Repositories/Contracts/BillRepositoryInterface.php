<?php

namespace App\Repositories\Contracts;

use App\Models\Bill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BillRepositoryInterface
{
    public function findByTransaction(string $transactionId): Bill;

    public function findByTransactionWithDetails(string $transactionId): Bill;

    /**
     * @param  array{transaction_id: string, amount: int, notes?: string|null, due_at?: string|null}  $data
     */
    public function create(array $data): Bill;

    public function update(Bill $bill, array $data): Bill;

    /**
     * Bills for transactions owned by the user and belonging to the customer.
     *
     * @param  array{
     *     status?: array<int, string>,
     *     invoice_number?: string,
     *     issued_at_from?: string,
     *     issued_at_to?: string,
     *     sort?: string
     * }  $filters
     */
    public function paginateForUserAndCustomer(int $userId, string $customerId, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Bills for all transactions owned by the user.
     *
     * @param  array{
     *     status?: array<int, string>,
     *     invoice_number?: string,
     *     issued_at_from?: string,
     *     issued_at_to?: string,
     *     sort?: string
     * }  $filters
     */
    public function paginateForUser(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Aggregate cash collected (paid bills) and accounts receivable (issued, unpaid bills).
     *
     * @param  array{
     *     customer_id?: string,
     *     paid_at_from?: string,
     *     paid_at_to?: string,
     *     as_of?: string
     * }  $filters
     * @return array{cash_received_total: int, accounts_receivable_total: int}
     */
    public function summarizeForUser(int $userId, array $filters = []): array;
}
