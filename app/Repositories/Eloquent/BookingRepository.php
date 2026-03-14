<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BookingRepository implements BookingRepositoryInterface
{
    public function __construct(protected Booking $model) {}

    /**
     * {@inheritdoc}
     */
    public function getCarIdsBookedInPeriod($start, $end): array
    {
        return $this->idsBookedInPeriod('car_id', $start, $end);
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverIdsBookedInPeriod($start, $end): array
    {
        return $this->idsBookedInPeriod('driver_id', $start, $end);
    }

    public function create(array $data): Booking
    {
        return $this->model->create($data);
    }

    public function find(string $id): Booking
    {
        return $this->model->with(['car', 'driver'])->findOrFail($id);
    }

    public function update(string $id, array $data): Booking
    {
        $booking = $this->model->findOrFail($id);
        $booking->update($data);

        return $booking->fresh(['car', 'driver']);
    }

    public function delete(string $id): bool
    {
        $booking = $this->model->findOrFail($id);

        return $booking->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getByTransaction(string $transactionId, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['car', 'driver'])
            ->where('transaction_id', $transactionId)
            ->orderByDesc('created_at');    

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Get distinct resource IDs (car_id or driver_id) that have overlapping bookings.
     * Overlap: start_date < request_end AND end_date > request_start.
     */
    private function idsBookedInPeriod(string $column, $start, $end): array
    {
        $start = $start instanceof Carbon ? $start->toDateString() : $start;
        $end = $end instanceof Carbon ? $end->toDateString() : $end;

        return $this->model->newQuery()
            ->where(function ($q) use ($start, $end) {
                $q->where('start_date', '<', $end)
                    ->where('end_date', '>', $start);
            })
            ->distinct()
            ->pluck($column)
            ->all();
    }
}
