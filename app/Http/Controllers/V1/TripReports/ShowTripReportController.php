<?php

namespace App\Http\Controllers\V1\TripReports;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TripReportResource;
use App\Models\PurchaseOrder;
use App\Models\TripReport;

class ShowTripReportController extends Controller
{
    public function __invoke(PurchaseOrder $purchaseOrder, TripReport $tripReport): TripReportResource
    {
        $this->ensureTripReportBelongsToPurchaseOrder($purchaseOrder, $tripReport);

        return new TripReportResource($tripReport->load('media'));
    }

    private function ensureTripReportBelongsToPurchaseOrder(PurchaseOrder $purchaseOrder, TripReport $tripReport): void
    {
        if ($tripReport->purchase_order_id !== $purchaseOrder->id) {
            abort(404);
        }
    }
}
