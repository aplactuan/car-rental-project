<?php

namespace App\Http\Controllers\V1\Cars;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\ListAvailableCarsRequest;
use App\Http\Resources\V1\CarResource;
use App\Repositories\Contracts\CarRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListAvailableCarsController extends Controller
{
    public function __construct(protected CarRepositoryInterface $car) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(ListAvailableCarsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->only(['make', 'model', 'type', 'number_of_seats']);
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
        $cars = $this->car->paginate($filters, $request->integer('per_page', 15));

        return CarResource::collection($cars);
    }
}
