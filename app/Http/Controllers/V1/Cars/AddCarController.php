<?php

namespace App\Http\Controllers\V1\Cars;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\AddCarRequest;
use App\Models\Car;
use App\Repositories\Contracts\CarRepositoryInterface;
use Illuminate\Http\Request;

class AddCarController extends Controller
{
    public function __construct(protected CarRepositoryInterface $car)
    {

    }
    /**
     * Handle the incoming request.
     */
    public function __invoke(AddCarRequest $request)
    {
        $this->car->create($request->validated());

        return response([
            'message' => 'Car Created',
        ], 201);
    }
}
