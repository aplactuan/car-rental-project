<?php

namespace App\Repositories\Eloquent;

use App\Models\Car;
use App\Models\Schedule;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use Carbon\Carbon;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    public function __construct(protected Schedule $model)
    {
    }

    /**
     * @inheritdoc
     */
    public function getCarIdsScheduledInPeriod($start, $end): array
    {
        $start = $start instanceof Carbon ? $start : Carbon::parse($start);
        $end = $end instanceof Carbon ? $end : Carbon::parse($end);

        return $this->model->newQuery()
            ->where('scheduleable_type', Car::class)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })
            ->distinct()
            ->pluck('scheduleable_id')
            ->all();
    }
}
