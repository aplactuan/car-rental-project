<?php

namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(protected Transaction $model) {}

    public function all()
    {
        return $this->model->with('bookings')->get();
    }

    public function find(string $id)
    {
        return $this->model->with(['bookings.car', 'bookings.driver'])->findOrFail($id);
    }

    public function findForUserAndCustomer(string $id, int $userId, string $customerId): Transaction
    {
        return $this->model
            ->with(['bookings.car', 'bookings.driver'])
            ->whereKey($id)
            ->where('user_id', $userId)
            ->where('customer_id', $customerId)
            ->firstOrFail();
    }

    public function create(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $bookings = $data['bookings'] ?? [];
            unset($data['bookings']);

            $transaction = $this->model->create($data);

            foreach ($bookings as $bookingData) {
                $transaction->bookings()->create($bookingData);
            }

            return $transaction->load(['bookings.car', 'bookings.driver']);
        });
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model->with('bookings')->paginate($perPage);
    }

    public function paginateByUser(int $userId, int $perPage = 15)
    {
        return $this->model->with('bookings')
            ->where('user_id', $userId)
            ->paginate($perPage);
    }

    public function paginateByUserAndCustomer(int $userId, string $customerId, int $perPage = 15)
    {
        return $this->model->with('bookings')
            ->where('user_id', $userId)
            ->where('customer_id', $customerId)
            ->paginate($perPage);
    }

    public function updateForUserAndCustomer(string $id, int $userId, string $customerId, array $data): Transaction
    {
        $transaction = $this->findForUserAndCustomer($id, $userId, $customerId);
        $transaction->update($data);

        return $transaction->load(['bookings.car', 'bookings.driver']);
    }

    public function deleteForUserAndCustomer(string $id, int $userId, string $customerId): bool
    {
        $transaction = $this->findForUserAndCustomer($id, $userId, $customerId);

        return (bool) $transaction->delete();
    }
}
