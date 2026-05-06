<?php

namespace App\Http\Controllers\V1\Drivers;

use App\Enums\DriverImportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\ImportDriversRequest;
use App\Http\Resources\V1\DriverImportResource;
use App\Jobs\ImportDriversJob;
use App\Models\DriverImport;
use Illuminate\Http\JsonResponse;

class ImportDriversController extends Controller
{
    public function __invoke(ImportDriversRequest $request): JsonResponse
    {
        $path = $request->file('file')->store('imports/drivers');

        $driverImport = DriverImport::create([
            'status' => DriverImportStatus::Pending,
            'file_path' => $path,
        ]);

        ImportDriversJob::dispatch($driverImport);

        return (new DriverImportResource($driverImport))
            ->response()
            ->setStatusCode(202);
    }
}
