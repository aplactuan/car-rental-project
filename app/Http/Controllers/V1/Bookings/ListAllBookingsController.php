<?php

namespace App\Http\Controllers\V1\Bookings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ListAllBookingsRequest;
use App\Http\Resources\V1\BookingResource;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListAllBookingsController extends Controller
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository
    ) {}

    public function __invoke(ListAllBookingsRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', 15);
        $user = $request->user()->loadMissing('driver');
        $filters = $request->filters();

        $bookings = $user->driver !== null && ! $user->isAdmin()
            ? $this->bookingRepository->getAllByDriver($user->driver->id, $filters, $perPage)
            : $this->bookingRepository->getAllByUser($user->id, $filters, $perPage);

        return BookingResource::collection($bookings);
    }
}
