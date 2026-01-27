<?php

namespace App\Http\Controllers\V1\Drivers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DriverResource;
use App\Models\Driver;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class SingleDriverController extends Controller
{
    use ApiResponses;

    public function __construct(protected DriverRepositoryInterface $driver)
    {

    }
    /**
     * Handle the incoming request.
     */
    public function __invoke(Driver $driver)
    {
        $driver = $this->driver->find($driver->id);

        if (!$driver) {
            return $this->error('Driver not found', 404);
        }

        return new DriverResource($driver);
    }
}
