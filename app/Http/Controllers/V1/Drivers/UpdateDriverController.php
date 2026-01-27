<?php

namespace App\Http\Controllers\V1\Drivers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DriverResource;
use App\Models\Driver;
use App\Repositories\Contracts\DriverRepositoryInterface;
use Illuminate\Http\Request;

class UpdateDriverController extends Controller
{
    public function __construct(protected DriverRepositoryInterface $driver)
    {

    }
    /**
     * Handle the incoming request.
     */
    public function __invoke(Driver $driver, Request $request)
    {
        $driver = $this->driver->find($driver->id);

        if (!$driver) {
            return $this->error('Driver not found', 404);
        }

        $driver = $this->driver->update($driver->id, $request->all());

        return new DriverResource($driver);
    }
}
