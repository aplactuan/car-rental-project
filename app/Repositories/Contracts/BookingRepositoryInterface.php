<?php

namespace App\Repositories\Contracts;

use Carbon\CarbonInterface;

interface BookingRepositoryInterface
{
    /**
     * Get car IDs that have overlapping bookings in the given period.
     * Overlap: booking.start_date < end AND booking.end_date > start.
     *
     * @param  CarbonInterface|string  $start
     * @param  CarbonInterface|string  $end
     * @return array<string>
     */
    public function getCarIdsBookedInPeriod($start, $end): array;

    /**
     * Get driver IDs that have overlapping bookings in the given period.
     *
     * @param  CarbonInterface|string  $start
     * @param  CarbonInterface|string  $end
     * @return array<string>
     */
    public function getDriverIdsBookedInPeriod($start, $end): array;
}
