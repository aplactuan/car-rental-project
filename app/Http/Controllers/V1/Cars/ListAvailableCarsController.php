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
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($startDate !== null || $endDate !== null) {
            $request->validate([
                'start_date' => ['required_with:end_date', 'date'],
                'end_date' => ['required_with:start_date', 'date', 'after_or_equal:start_date'],
            ]);

            $cars = $this->car->availableInPeriod($startDate, $endDate);

            return CarResource::collection($cars);
        }

        $filters = $request->only(['make', 'model', 'type', 'number_of_seats']);
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        if (empty($filters)) {
            $cars = $this->car->all();
        } else {
            $cars = $this->car->filter($filters);
        }

        return CarResource::collection($cars);
    }
}