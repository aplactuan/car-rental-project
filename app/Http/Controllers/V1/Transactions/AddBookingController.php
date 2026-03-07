<?php

namespace App\Http\Controllers\V1\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\AddBookingRequest;
use App\Http\Resources\V1\BookingResource;
use App\Models\Transaction;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Http\JsonResponse;

class AddBookingController extends Controller
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository,
        protected ScheduleRepositoryInterface $scheduleRepository
    ) {}

    public function __invoke(AddBookingRequest $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        $startDate = $request->validated('start_date');
        $endDate = $request->validated('end_date');
        $carId = $request->validated('car_id');
        $driverId = $request->validated('driver_id');

        $scheduledCarIds = $this->scheduleRepository->getCarIdsScheduledInPeriod($startDate, $endDate);
        $scheduledDriverIds = $this->scheduleRepository->getDriverIdsScheduledInPeriod($startDate, $endDate);

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

        $booking = $this->bookingRepository->create([
            'transaction_id' => $transaction->id,
            'car_id' => $request->validated('car_id'),
            'driver_id' => $request->validated('driver_id'),
            'note' => $request->validated('note'),
            'start_date' => $request->validated('start_date'),
            'end_date' => $request->validated('end_date'),
        ]);

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }
}
