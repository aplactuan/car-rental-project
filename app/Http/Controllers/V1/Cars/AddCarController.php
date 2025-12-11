<?php

namespace App\Http\Controllers\V1\Cars;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\AddCarRequest;
use App\Http\Resources\V1\CarResource;
use App\Models\Car;
use App\Repositories\Contracts\CarRepositoryInterface;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class AddCarController extends Controller
{
    use ApiResponses;

    public function __construct(protected CarRepositoryInterface $car)
    {

    }
    /**
     * Handle the incoming request.
     */
    public function __invoke(AddCarRequest $request)
    {
        $car = $this->car->create($request->validated());

        return new CarResource($car);
    }
}
