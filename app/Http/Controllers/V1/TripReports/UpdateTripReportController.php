<?php

namespace App\Http\Controllers\V1\TripReports;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripReport\UpdateTripReportRequest;
use App\Http\Resources\V1\TripReportResource;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Support\Media\MediaUploader;

class UpdateTripReportController extends Controller
{
    public function __construct(protected MediaUploader $mediaUploader) {}

    public function __invoke(
        UpdateTripReportRequest $request,
        PurchaseOrder $purchaseOrder,
        TripReport $tripReport,
    ): TripReportResource {
        $this->ensureTripReportBelongsToPurchaseOrder($purchaseOrder, $tripReport);

        $data = $request->validated();
        unset($data['trip_report_image']);

        $tripReport->update($data);

        if ($request->hasFile('trip_report_image')) {
            $this->mediaUploader->add(
                $tripReport,
                $request->file('trip_report_image'),
                TripReport::TRIP_REPORT_IMAGE_MEDIA_COLLECTION
            );
        }

        return new TripReportResource($tripReport->fresh('media'));
    }

    private function ensureTripReportBelongsToPurchaseOrder(PurchaseOrder $purchaseOrder, TripReport $tripReport): void
    {
        if ($tripReport->purchase_order_id !== $purchaseOrder->id) {
            abort(404);
        }
    }
}
