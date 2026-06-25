<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use App\Models\TripReport;
use Illuminate\Database\Eloquent\Collection;

interface TripReportRepositoryInterface
{
    /**
     * @return Collection<int, TripReport>
     */
    public function listForBooking(Booking $booking): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForBooking(Booking $booking, array $data): TripReport;

    public function find(string $id): TripReport;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TripReport $tripReport, array $data): TripReport;

    public function delete(TripReport $tripReport): void;
}
