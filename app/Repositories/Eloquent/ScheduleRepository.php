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
    public function getCarIdsScheduledInPeriod($start, $end, ?string $excludeCarId = null, $excludePeriodStart = null, $excludePeriodEnd = null): array
    {
        return $this->idsScheduledInPeriod(Car::class, $start, $end, $excludeCarId, $excludePeriodStart, $excludePeriodEnd);
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverIdsScheduledInPeriod($start, $end, ?string $excludeDriverId = null, $excludePeriodStart = null, $excludePeriodEnd = null): array
    {
        return $this->idsScheduledInPeriod(Driver::class, $start, $end, $excludeDriverId, $excludePeriodStart, $excludePeriodEnd);
    }

    /**
     * Get distinct scheduleable IDs for a model class with overlapping schedules.
     *
     * @param  class-string  $scheduleableType
     */
    private function idsScheduledInPeriod(string $scheduleableType, $start, $end, ?string $excludeId = null, $excludeStart = null, $excludeEnd = null): array
    {
        $start = $start instanceof Carbon ? $start : Carbon::parse($start);
        $end = $end instanceof Carbon ? $end : Carbon::parse($end);

        $query = $this->model->newQuery()
            ->where('scheduleable_type', $scheduleableType)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            });

        if ($excludeId !== null && $excludeStart !== null && $excludeEnd !== null) {
            $excludeStart = $excludeStart instanceof Carbon ? $excludeStart : Carbon::parse($excludeStart);
            $excludeEnd = $excludeEnd instanceof Carbon ? $excludeEnd : Carbon::parse($excludeEnd);
            $query->whereNot(function ($q) use ($excludeId, $excludeStart, $excludeEnd) {
                $q->where('scheduleable_id', $excludeId)
                    ->where('start_time', '<', $excludeEnd)
                    ->where('end_time', '>', $excludeStart);
            });
        }

        return $query->distinct()->pluck('scheduleable_id')->all();
    }
}
