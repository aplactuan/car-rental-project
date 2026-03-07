<?php

namespace App\Http\Controllers\V1\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Http\Resources\V1\BookingResource;
use App\Models\Booking;
use App\Models\Transaction;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class UpdateBookingController extends Controller
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository,
        protected ScheduleRepositoryInterface $scheduleRepository
    ) {}

    public function __invoke(UpdateBookingRequest $request, Transaction $transaction, Booking $booking): BookingResource|JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        if ($booking->transaction_id !== $transaction->id) {
            abort(404);
        }

        $data = $request->validated();
        $carId = $data['car_id'] ?? $booking->car_id;
        $driverId = $data['driver_id'] ?? $booking->driver_id;
        $startDate = $data['start_date'] ?? $booking->start_date;
        $endDate = $data['end_date'] ?? $booking->end_date;

        $startTime = Carbon::parse($startDate)->startOfDay();
        $endTime = Carbon::parse($endDate)->endOfDay();

        $excludeStart = Carbon::parse($booking->start_date)->startOfDay();
        $excludeEnd = Carbon::parse($booking->end_date)->endOfDay();

        $scheduledCarIds = $this->scheduleRepository->getCarIdsScheduledInPeriod(
            $startTime,
            $endTime,
            $booking->car_id,
            $excludeStart,
            $excludeEnd
        );
        $scheduledDriverIds = $this->scheduleRepository->getDriverIdsScheduledInPeriod(
            $startTime,
            $endTime,
            $booking->driver_id,
            $excludeStart,
            $excludeEnd
        );

        if (in_array($carId, $scheduledCarIds)) {
            return response()->json([
                'message' => 'The selected car is not available for the given dates.',
            ], 422);
        }

        if (in_array($driverId, $scheduledDriverIds)) {
            return response()->json([
                'message' => 'The selected driver is not available for the given dates.',
            ], 422);
        }

        $booking = $this->bookingRepository->update($booking->id, $data);

        return new BookingResource($booking);
    }
}
