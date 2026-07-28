<?php

namespace App\Http\Controllers\V1\TripReports;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripReport\CreateTripReportRequest;
use App\Http\Resources\V1\TripReportResource;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use Illuminate\Http\JsonResponse;

class AddTripReportController extends Controller
{
    public function __invoke(CreateTripReportRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $data = $request->validated();
        unset($data['trip_report_image']);

        $tripReport = $purchaseOrder->tripReports()->create($data);

        if ($request->hasFile('trip_report_image')) {
            $tripReport->addMedia($request->file('trip_report_image'))
                ->toMediaCollection(TripReport::TRIP_REPORT_IMAGE_MEDIA_COLLECTION);
        }

        return (new TripReportResource($tripReport->load('media')))
            ->response()
            ->setStatusCode(201);
    }
}
