<?php

namespace App\Http\Controllers\V1\Cars;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CarResource;
use App\Models\Car;
use App\Repositories\Contracts\CarRepositoryInterface;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class SingleCarController extends Controller
{
    use ApiResponses;

    public function __construct(protected CarRepositoryInterface $car) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Car $car)
    {
        $car = $this->car->find($car->id);

        if (! $car) {
            return $this->error('Car not found', 404);
        }

        return new CarResource($car);
    }
}
