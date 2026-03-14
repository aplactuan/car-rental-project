<?php

namespace App\Repositories\Contracts;

interface TransactionRepositoryInterface
{
    public function all();

    public function find(string $id);

    public function findForUserAndCustomer(string $id, int $userId, string $customerId);

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateByUser(int $userId, int $perPage = 15);

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateByUserAndCustomer(int $userId, string $customerId, int $perPage = 15);

    /**
     * Create transaction with nested bookings in a single DB transaction.
     *
     * @param  array{user_id: int, bookings: array<int, array{car_id: string, driver_id: string, note?: string, start_date: string, end_date: string}>}  $data
     * @return \App\Models\Transaction
     */
    public function create(array $data);

    public function updateForUserAndCustomer(string $id, int $userId, string $customerId, array $data);

    public function deleteForUserAndCustomer(string $id, int $userId, string $customerId): bool;

    public function paginate(int $perPage = 15);
}
