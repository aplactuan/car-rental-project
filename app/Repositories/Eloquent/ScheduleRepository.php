<?php

namespace App\Repositories\Eloquent;

use App\Models\Car;
use App\Models\Driver;
use App\Models\Schedule;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use Carbon\Carbon;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    public function __construct(protected Schedule $model) {}

    /**
     * {@inheritdoc}
     */
    public function getCarIdsScheduledInPeriod($start, $end): array
    {
        return $this->idsScheduledInPeriod(Car::class, $start, $end);
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverIdsScheduledInPeriod($start, $end): array
    {
        return $this->idsScheduledInPeriod(Driver::class, $start, $end);
    }

    /**
     * Get distinct scheduleable IDs for a model class with overlapping schedules.
     *
     * @param  class-string  $scheduleableType
     */
    private function idsScheduledInPeriod(string $scheduleableType, $start, $end): array
    {
        $start = $start instanceof Carbon ? $start : Carbon::parse($start);
        $end = $end instanceof Carbon ? $end : Carbon::parse($end);

        return $this->model->newQuery()
            ->where('scheduleable_type', $scheduleableType)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })
            ->distinct()
            ->pluck('scheduleable_id')
            ->all();
    }
}
