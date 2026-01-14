<?php

namespace App\Http\Controllers\V1\Cars;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CarResource;
use App\Repositories\Contracts\CarRepositoryInterface;
use Illuminate\Http\Request;

class ListAvailableCarsController extends Controller
{
    public function __construct(protected CarRepositoryInterface $car)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // For now "available" = all cars in the system
        $cars = $this->car->all();

        return CarResource::collection($cars);
    }
}