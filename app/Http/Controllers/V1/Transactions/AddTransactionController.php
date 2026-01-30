<?php

namespace App\Http\Controllers\V1\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\AddTransactionRequest;
use App\Http\Resources\V1\TransactionResource;
use App\Repositories\Contracts\CarRepositoryInterface;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Support\JsonApiError;
use Illuminate\Http\JsonResponse;

class AddTransactionController extends Controller
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository,
        protected CarRepositoryInterface $carRepository,
        protected DriverRepositoryInterface $driverRepository
    ) {
    }

    public function __invoke(AddTransactionRequest $request): JsonResponse|TransactionResource
    {
        $bookings = $request->validated('bookings');

        foreach ($bookings as $index => $booking) {
            $start = $booking['start_date'];
            $end = $booking['end_date'];
            $availableCars = $this->carRepository->availableInPeriod($start, $end);
            $availableDriverIds = $this->driverRepository->availableInPeriod($start, $end)->pluck('id')->all();

            if (! $availableCars->contains('id', $booking['car_id'])) {
                return JsonApiError::response(
                    '422',
                    'Unprocessable Entity',
                    'The selected car is not available for the given dates.',
                    "/data/attributes/bookings/{$index}/car_id",
                    null,
                    422
                );
            }

            if (! in_array($booking['driver_id'], $availableDriverIds, true)) {
                return JsonApiError::response(
                    '422',
                    'Unprocessable Entity',
                    'The selected driver is not available for the given dates.',
                    "/data/attributes/bookings/{$index}/driver_id",
                    null,
                    422
                );
            }
        }

        $data = array_merge(
            ['user_id' => $request->user()->id],
            ['bookings' => $bookings]
        );

        $transaction = $this->transactionRepository->create($data);

        return (new TransactionResource($transaction))->response()->setStatusCode(201);
    }
}
