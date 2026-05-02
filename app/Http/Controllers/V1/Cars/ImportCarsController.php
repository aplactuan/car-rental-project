<?php

namespace App\Http\Controllers\V1\Cars;

use App\Enums\CarImportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Car\ImportCarsRequest;
use App\Http\Resources\V1\CarImportResource;
use App\Jobs\ImportCarsJob;
use App\Models\CarImport;
use Illuminate\Http\JsonResponse;

class ImportCarsController extends Controller
{
    public function __invoke(ImportCarsRequest $request): JsonResponse
    {
        $path = $request->file('file')->store('imports/cars');

        $carImport = CarImport::create([
            'status' => CarImportStatus::Pending,
            'file_path' => $path,
        ]);

        ImportCarsJob::dispatch($carImport);

        return (new CarImportResource($carImport))
            ->response()
            ->setStatusCode(202);
    }
}
