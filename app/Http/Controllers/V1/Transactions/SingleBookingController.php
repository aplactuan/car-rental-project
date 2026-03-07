<?php

namespace App\Http\Controllers\V1\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookingResource;
use App\Models\Booking;
use App\Models\Transaction;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Http\Request;

class SingleBookingController extends Controller
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository
    ) {}

    public function __invoke(Request $request, Transaction $transaction, Booking $booking): BookingResource
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        if ($booking->transaction_id !== $transaction->id) {
            abort(404);
        }

        $booking = $this->bookingRepository->find($booking->id);

        return new BookingResource($booking);
    }
}
