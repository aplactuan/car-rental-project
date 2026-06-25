<?php

namespace App\Http\Controllers\V1\TripReports;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripReport\ListTripReportsRequest;
use App\Http\Resources\V1\TripReportResource;
use App\Models\Booking;
use App\Models\Transaction;
use App\Repositories\Contracts\TripReportRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListTripReportsController extends Controller
{
    public function __construct(
        protected TripReportRepositoryInterface $tripReportRepository
    ) {}

    public function __invoke(ListTripReportsRequest $request, Transaction $transaction, Booking $booking): AnonymousResourceCollection
    {
        if ($booking->transaction_id !== $transaction->id) {
            abort(404);
        }

        return TripReportResource::collection($this->tripReportRepository->listForBooking($booking));
    }
}
