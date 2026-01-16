<?php

namespace App\Http\Controllers\V1\Cars;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\UpdateCarRequest;
use App\Http\Resources\V1\CarResource;
use App\Repositories\Contracts\CarRepositoryInterface;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class UpdateCarController extends Controller
{
    use ApiResponses;

    public function __construct(protected CarRepositoryInterface $car)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateCarRequest $request, string $car)
    {
        $car = $this->car->update($car, $request->validated());

        return new CarResource($car);
    }
}
