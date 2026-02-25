<?php

namespace App\Repositories\Contracts;

use Carbon\CarbonInterface;

interface ScheduleRepositoryInterface
{
    /**
     * Get car IDs that have overlapping schedules in the given period.
     * Overlap: schedule.start_time < end AND schedule.end_time > start.
     *
     * @param  CarbonInterface|string  $start
     * @param  CarbonInterface|string  $end
     * @return array<string>
     */
    public function getCarIdsScheduledInPeriod($start, $end): array;
}
