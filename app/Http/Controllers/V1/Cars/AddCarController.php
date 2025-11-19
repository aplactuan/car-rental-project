<?php

namespace App\Http\Controllers\V1\Cars;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\AddCarRequest;
use App\Models\Car;
use Illuminate\Http\Request;

class AddCarController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AddCarRequest $request)
    {
        Car::create($request->validated());

        return response([
            'message' => 'Car Created',
        ], 201);
    }
}
