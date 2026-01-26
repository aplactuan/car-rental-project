<?php

namespace App\Http\Controllers\V1\Cars;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SingleCarController extends Controller
{
    use ApiResponses;

    public function __construct(protected CarRepositoryInterface $car)
    {

    }
    /**
     * Handle the incoming request.
     */
    public function __invoke(Car $car)
    {
        $car = $this->car->find($car);

        return new CarResource($car);
    }
}
