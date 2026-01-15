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
        $filters = $request->only(['make', 'model', 'type', 'number_of_seats']);
        
        // Remove empty filters
        $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');
        
        if (empty($filters)) {
            $cars = $this->car->all();
        } else {
            $cars = $this->car->filter($filters);
        }

        return CarResource::collection($cars);
    }
}