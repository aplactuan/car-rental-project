<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\Schedule;
use Carbon\Carbon;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     * Create schedules for the driver and car with the booking's start_time and end_time.
     */
    public function created(Booking $booking): void
    {
        $startTime = Carbon::parse($booking->start_date)->startOfDay();
        $endTime = Carbon::parse($booking->end_date)->endOfDay();

        $scheduleData = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'booking_id' => $booking->id,
        ];

        if ($booking->driver_id) {
            $booking->driver->schedules()->create($scheduleData);
        }

        if ($booking->car_id) {
            $booking->car->schedules()->create($scheduleData);
        }
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "deleted" event.
     */
    public function deleted(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "restored" event.
     */
    public function restored(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "force deleted" event.
     */
    public function forceDeleted(Booking $booking): void
    {
        //
    }
}
