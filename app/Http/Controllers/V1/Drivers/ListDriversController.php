<?php

namespace App\Http\Controllers\V1\Drivers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\ListDriversRequest;
use App\Http\Resources\V1\DriverResource;
use App\Repositories\Contracts\DriverRepositoryInterface;

class ListDriversController extends Controller
{
    public function __construct(protected DriverRepositoryInterface $driver)
    {
    }

    public function __invoke(ListDriversRequest $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $perPage = $request->input('per_page', 15);

        if ($startDate !== null || $endDate !== null) {
            $request->validate([
                'start_date' => ['required_with:end_date', 'date'],
                'end_date' => ['required_with:start_date', 'date', 'after_or_equal:start_date'],
            ]);

            $drivers = $this->driver->availableInPeriod($startDate, $endDate, $perPage);

            return DriverResource::collection($drivers);
        }

        $drivers = $this->driver->paginate($perPage);

        return DriverResource::collection($drivers);
    }
}

