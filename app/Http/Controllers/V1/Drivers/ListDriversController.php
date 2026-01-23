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
        $perPage = $request->input('per_page', 15);

        $drivers = $this->driver->paginate($perPage);

        return DriverResource::collection($drivers);
    }
}

