<?php

namespace App\Http\Controllers\V1\Availability;

use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\ListAvailabilityRequest;
use App\Http\Resources\V1\CarResource;
use App\Http\Resources\V1\DriverResource;
use App\Models\Car;
use App\Models\Driver;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListAvailabilityController extends Controller
{
    public function __construct(protected ScheduleRepositoryInterface $scheduleRepository)
    {
    }

    public function __invoke(ListAvailabilityRequest $request): AnonymousResourceCollection
    {
        $type = $request->string('type')->toString();
        $start = $request->string('start')->toString();
        $end = $request->string('end')->toString();

        if ($type === 'car') {
            $scheduledCarIds = $this->scheduleRepository->getCarIdsScheduledInPeriod($start, $end);

            $cars = Car::query()
                ->whereNotIn('id', $scheduledCarIds)
                ->get();

            return CarResource::collection($cars);
        }

        $scheduledDriverIds = $this->scheduleRepository->getDriverIdsScheduledInPeriod($start, $end);

        $drivers = Driver::query()
            ->whereNotIn('id', $scheduledDriverIds)
            ->get();

        return DriverResource::collection($drivers);
    }
}
