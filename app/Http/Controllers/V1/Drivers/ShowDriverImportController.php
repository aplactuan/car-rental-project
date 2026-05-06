<?php

namespace App\Http\Controllers\V1\Drivers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DriverImportResource;
use App\Models\DriverImport;
use Illuminate\Http\JsonResponse;

class ShowDriverImportController extends Controller
{
    public function __invoke(DriverImport $driverImport): JsonResponse
    {
        return (new DriverImportResource($driverImport))->response();
    }
}
