<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Carbon\Carbon;

class BookingRepository implements BookingRepositoryInterface
{
    public function __construct(protected Booking $model)
    {
    }

    /**
     * @inheritdoc
     */
    public function getCarIdsBookedInPeriod($start, $end): array
    {
        return $this->idsBookedInPeriod('car_id', $start, $end);
    }

    /**
     * @inheritdoc
     */
    public function getDriverIdsBookedInPeriod($start, $end): array
    {
        return $this->idsBookedInPeriod('driver_id', $start, $end);
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
