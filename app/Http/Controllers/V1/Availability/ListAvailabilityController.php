<?php

namespace App\Http\Controllers\V1\Availability;

use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\ListAvailabilityRequest;
use App\Http\Resources\V1\CarResource;
use App\Http\Resources\V1\DriverResource;
use App\Repositories\Contracts\CarRepositoryInterface;
use App\Repositories\Contracts\DriverRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListAvailabilityController extends Controller
{
    public function __construct(
        protected CarRepositoryInterface $car,
        protected DriverRepositoryInterface $driver
    ) {
    }

    public function __invoke(ListAvailabilityRequest $request): AnonymousResourceCollection
    {
        $type = $request->string('type')->toString();
        $start = $request->string('start')->toString();
        $end = $request->string('end')->toString();

        if ($type === 'car') {
            $cars = $this->car->availableInPeriod($start, $end);

            return CarResource::collection($cars);
        }

        $drivers = $this->driver->availableInPeriod($start, $end);

        return DriverResource::collection($drivers);
    }
}
