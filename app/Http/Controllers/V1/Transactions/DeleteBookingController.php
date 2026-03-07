<?php

namespace App\Http\Controllers\V1\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Transaction;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteBookingController extends Controller
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository
    ) {}

    public function __invoke(Request $request, Transaction $transaction, Booking $booking): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        if ($booking->transaction_id !== $transaction->id) {
            abort(404);
        }

        $this->bookingRepository->delete($booking->id);

        return response()->json(null, 204);
    }
}
