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
     * @param  string|null  $excludeCarId  Car ID to exclude (e.g. when updating a booking)
     * @param  CarbonInterface|string|null  $excludePeriodStart  Start of period to exclude for the car
     * @param  CarbonInterface|string|null  $excludePeriodEnd  End of period to exclude for the car
     * @return array<string>
     */
    public function getCarIdsScheduledInPeriod($start, $end, ?string $excludeCarId = null, $excludePeriodStart = null, $excludePeriodEnd = null): array;

    /**
     * Get driver IDs that have overlapping schedules in the given period.
     * Overlap: schedule.start_time < end AND schedule.end_time > start.
     *
     * @param  CarbonInterface|string  $start
     * @param  CarbonInterface|string  $end
     * @param  string|null  $excludeDriverId  Driver ID to exclude (e.g. when updating a booking)
     * @param  CarbonInterface|string|null  $excludePeriodStart  Start of period to exclude for the driver
     * @param  CarbonInterface|string|null  $excludePeriodEnd  End of period to exclude for the driver
     * @return array<string>
     */
    public function getDriverIdsScheduledInPeriod($start, $end, ?string $excludeDriverId = null, $excludePeriodStart = null, $excludePeriodEnd = null): array;
}
