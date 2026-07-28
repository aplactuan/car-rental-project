<?php

namespace App\Http\Controllers\V1\TripReports;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TripReportResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListTripReportsController extends Controller
{
    public function __invoke(PurchaseOrder $purchaseOrder): AnonymousResourceCollection
    {
        return TripReportResource::collection(
            $purchaseOrder->tripReports()
                ->with('media')
                ->latest('report_date')
                ->get()
        );
    }
}
