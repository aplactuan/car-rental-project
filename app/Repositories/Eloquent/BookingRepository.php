<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Support\BookingListFilters;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
    public function getByTransaction(string $transactionId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['car', 'driver'])
            ->where('transaction_id', $transactionId)
            ->when(
                isset($filters[BookingListFilters::PARAM_CAR_ID]),
                fn (Builder $builder) => $builder->where('car_id', $filters[BookingListFilters::PARAM_CAR_ID])
            )
            ->when(
                isset($filters[BookingListFilters::PARAM_DRIVER_ID]),
                fn (Builder $builder) => $builder->where('driver_id', $filters[BookingListFilters::PARAM_DRIVER_ID])
            )
            ->when(
                isset($filters[BookingListFilters::PARAM_STATUS]),
                function (Builder $builder) use ($filters): void {
                    $statusConstraint = BookingListFilters::statusConstraint($filters[BookingListFilters::PARAM_STATUS]);

                    $builder->where($statusConstraint['column'], $statusConstraint['operator'], now());
                }
            )
            ->when(
                isset($filters[BookingListFilters::PARAM_PERIOD]),
                function (Builder $builder) use ($filters): void {
                    [$periodStart, $periodEnd] = BookingListFilters::periodBounds($filters[BookingListFilters::PARAM_PERIOD]);

                    $builder->where('start_date', '<', $periodEnd)
                        ->where('end_date', '>=', $periodStart);
                }
            )
            ->orderByDesc('created_at')
            ->orderByDesc('start_date');

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
