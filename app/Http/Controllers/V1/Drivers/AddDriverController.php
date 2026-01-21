<?php

namespace App\Http\Controllers\V1\Drivers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\AddDriverRequest;
use App\Http\Resources\V1\DriverResource;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Traits\ApiResponses;

class AddDriverController extends Controller
{
    use ApiResponses;

    public function __construct(protected DriverRepositoryInterface $driver)
    {
    }

    public function __invoke(AddDriverRequest $request)
    {
        $driver = $this->driver->create($request->validated());

        return (new DriverResource($driver))
            ->response()
            ->setStatusCode(201);
    }
}

