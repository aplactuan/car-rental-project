<?php

namespace App\Http\Controllers\V1\TripReports;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripReport\AddTripReportRequest;
use App\Http\Resources\V1\TripReportResource;
use App\Models\Booking;
use App\Models\Transaction;
use App\Repositories\Contracts\TripReportRepositoryInterface;
use Illuminate\Http\JsonResponse;

class AddTripReportController extends Controller
{
    public function __construct(
        protected TripReportRepositoryInterface $tripReportRepository
    ) {}

    public function __invoke(AddTripReportRequest $request, Transaction $transaction, Booking $booking): JsonResponse
    {
        if ($booking->transaction_id !== $transaction->id) {
            abort(404);
        }

        $tripReport = $this->tripReportRepository->createForBooking($booking, $request->validated());

        return (new TripReportResource($tripReport))->response()->setStatusCode(201);
    }
}
