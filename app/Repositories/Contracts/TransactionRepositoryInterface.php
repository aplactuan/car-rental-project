<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    public function all();

    public function find(string $id);

    public function findForUserAndCustomer(string $id, int $userId, string $customerId);

    /**
     * @return LengthAwarePaginator
     */
    public function paginateByUser(int $userId, int $perPage = 15);

    /**
     * @return LengthAwarePaginator
     */
    public function paginateByUserAndCustomer(int $userId, string $customerId, int $perPage = 15);

    /**
     * Create transaction with nested bookings in a single DB transaction.
     *
     * @param  array{user_id: int, customer_id: string, name: string, bookings?: array<int, array{car_id: string, driver_id: string, note?: string, start_date: string, end_date: string}>}  $data
     * @return Transaction
     */
    public function create(array $data);

    public function updateForUserAndCustomer(string $id, int $userId, string $customerId, array $data);

    public function deleteForUserAndCustomer(string $id, int $userId, string $customerId): bool;

    public function paginate(int $perPage = 15);
}
