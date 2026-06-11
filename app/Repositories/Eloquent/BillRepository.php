<?php

namespace App\Repositories\Eloquent;

use App\Models\Bill;
use App\Repositories\Contracts\BillRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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

    public function findByTransactionWithDetails(string $transactionId): Bill
    {
        return $this->model->newQuery()
            ->with(['transaction.customer', 'transaction.bookings.car', 'transaction.bookings.driver'])
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

    /**
     * {@inheritdoc}
     */
    public function paginateForUserAndCustomer(int $userId, string $customerId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseQueryForUserTransactions($userId, $customerId)
            ->withSum('payments', 'amount');

        $this->applyListFilters($query, $filters);
        $this->applyListSort($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function paginateForUser(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseQueryForUserTransactions($userId)
            ->withSum('payments', 'amount');

        $this->applyListFilters($query, $filters);
        $this->applyListSort($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function summarizeForUser(int $userId, array $filters = []): array
    {
        $customerId = $filters['customer_id'] ?? null;

        $cashQuery = $this->baseQueryForUserTransactions($userId, $customerId)
            ->where('status', 'paid');

        if (isset($filters['paid_at_from'])) {
            $cashQuery->where('paid_at', '>=', Carbon::parse($filters['paid_at_from'])->startOfDay());
        }

        if (isset($filters['paid_at_to'])) {
            $cashQuery->where('paid_at', '<=', Carbon::parse($filters['paid_at_to'])->endOfDay());
        }

        $unpaidQuery = $this->baseQueryForUserTransactions($userId, $customerId)
            ->whereIn('status', ['issued', 'partially_paid']);

        if (isset($filters['as_of'])) {
            $unpaidQuery->where('issued_at', '<=', Carbon::parse($filters['as_of'])->endOfDay());
        }

        $totalUnpaid = (int) $unpaidQuery
            ->selectRaw('COALESCE(SUM(amount - COALESCE((SELECT SUM(bp.amount) FROM bill_payments bp WHERE bp.bill_id = bills.id), 0)), 0) as total_unpaid')
            ->value('total_unpaid');

        return [
            'total_paid' => (int) $cashQuery->sum('amount'),
            'total_unpaid' => $totalUnpaid,
        ];
    }

    private function baseQueryForUserTransactions(int $userId, ?string $customerId = null): Builder
    {
        return $this->model->newQuery()
            ->whereHas('transaction', function (Builder $builder) use ($userId, $customerId): void {
                $builder->where('user_id', $userId);
                if ($customerId !== null) {
                    $builder->where('customer_id', $customerId);
                }
            });
    }

    /**
     * @param  array{
     *     status?: array<int, string>,
     *     invoice_number?: string,
     *     issued_at_from?: string,
     *     issued_at_to?: string,
     *     sort?: string
     * }  $filters
     */
    private function applyListFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (isset($filters['invoice_number'])) {
            $query->where('invoice_number', 'like', '%'.$filters['invoice_number'].'%');
        }

        if (isset($filters['issued_at_from'])) {
            $from = Carbon::parse($filters['issued_at_from'])->startOfDay();
            $query->where('issued_at', '>=', $from);
        }

        if (isset($filters['issued_at_to'])) {
            $to = Carbon::parse($filters['issued_at_to'])->endOfDay();
            $query->where('issued_at', '<=', $to);
        }
    }

    /**
     * @param  array{sort?: string}  $filters
     */
    private function applyListSort(Builder $query, array $filters): void
    {
        $sort = $filters['sort'] ?? '-created_at';

        match ($sort) {
            'issued_at' => $query->orderByRaw('issued_at IS NULL')->orderBy('issued_at')->orderBy('created_at'),
            '-issued_at' => $query->orderByRaw('issued_at IS NULL')->orderByDesc('issued_at')->orderByDesc('created_at'),
            'created_at' => $query->orderBy('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }

    private function nextBillNumber(): string
    {
        $prefix = now()->format('Ymd');

        $latestTodayCount = $this->model->newQuery()
            ->whereDate('created_at', now()->toDateString())
            ->select('id')
            ->lockForUpdate()
            ->get()
            ->count();

        $sequence = str_pad((string) ($latestTodayCount + 1), 4, '0', STR_PAD_LEFT);

        return "INV-{$prefix}-{$sequence}";
    }
}
