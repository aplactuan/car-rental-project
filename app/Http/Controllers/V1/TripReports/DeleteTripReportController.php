<?php

namespace App\Http\Controllers\V1\TripReports;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripReport\UpdateTripReportRequest;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\TripReport;
use App\Repositories\Contracts\TripReportRepositoryInterface;
use Illuminate\Http\JsonResponse;

class DeleteTripReportController extends Controller
{
    public function __construct(
        protected TripReportRepositoryInterface $tripReportRepository
    ) {}

    public function __invoke(UpdateTripReportRequest $request, Transaction $transaction, Booking $booking, TripReport $tripReport): JsonResponse
    {
        if ($booking->transaction_id !== $transaction->id) {
            abort(404);
        }

        if ($tripReport->booking_id !== $booking->id) {
            abort(404);
        }

        $this->tripReportRepository->delete($tripReport);

        return response()->json(null, 204);
    }
}
