<?php

namespace App\Http\Controllers\V1\Cars;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CarImportResource;
use App\Models\CarImport;
use Illuminate\Http\JsonResponse;

class ShowCarImportController extends Controller
{
    public function __invoke(CarImport $carImport): JsonResponse
    {
        return (new CarImportResource($carImport))->response();
    }
}
