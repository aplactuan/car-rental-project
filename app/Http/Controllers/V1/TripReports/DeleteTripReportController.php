<?php

namespace App\Http\Controllers\V1\TripReports;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use Illuminate\Http\JsonResponse;

class DeleteTripReportController extends Controller
{
    public function __invoke(PurchaseOrder $purchaseOrder, TripReport $tripReport): JsonResponse
    {
        if ($tripReport->purchase_order_id !== $purchaseOrder->id) {
            abort(404);
        }

        $tripReport->delete();

        return response()->json(null, 204);
    }
}
