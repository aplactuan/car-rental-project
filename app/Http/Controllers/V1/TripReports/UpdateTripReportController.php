<?php

namespace App\Http\Controllers\V1\TripReports;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripReport\UpdateTripReportRequest;
use App\Http\Resources\V1\TripReportResource;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\TripReport;
use App\Repositories\Contracts\TripReportRepositoryInterface;

class UpdateTripReportController extends Controller
{
    public function __construct(
        protected TripReportRepositoryInterface $tripReportRepository
    ) {}

    public function __invoke(UpdateTripReportRequest $request, Transaction $transaction, Booking $booking, TripReport $tripReport): TripReportResource
    {
        if ($booking->transaction_id !== $transaction->id) {
            abort(404);
        }

        if ($tripReport->booking_id !== $booking->id) {
            abort(404);
        }

        $tripReport = $this->tripReportRepository->update($tripReport, $request->validated());

        return new TripReportResource($tripReport);
    }
}
