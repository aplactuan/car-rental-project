<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

    public function create(array $data): Booking;

    public function find(string $id): Booking;

    public function update(string $id, array $data): Booking;

    public function delete(string $id): bool;

    /**
     * @return Collection<int, Booking>|LengthAwarePaginator
     */
    public function getByTransaction(string $transactionId, ?int $perPage = null): Collection|LengthAwarePaginator;
}
