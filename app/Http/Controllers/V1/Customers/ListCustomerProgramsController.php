<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProgramResource;
use App\Models\Customer;
use App\Models\PurchaseOrder;

class ListCustomerProgramsController extends Controller
{
    public function __invoke(Customer $customer)
    {
        $programs = $customer->programs()
            ->withCount([
                'purchaseOrders as purchase_orders_count' => fn ($query) => $query->where('customer_id', $customer->id),
            ])
            ->withSum([
                'purchaseOrders as purchase_orders_sum_amount' => fn ($query) => $query->where('customer_id', $customer->id),
            ], 'amount')
            ->latest()
            ->get();

        $unprogrammed = PurchaseOrder::query()
            ->where('customer_id', $customer->id)
            ->whereNull('program_id')
            ->selectRaw('COUNT(*) as purchase_order_count, COALESCE(SUM(amount), 0) as purchase_order_total')
            ->first();

        return ProgramResource::collection($programs)->additional([
            'meta' => [
                'unprogrammedPurchaseOrderCount' => (int) $unprogrammed->purchase_order_count,
                'unprogrammedPurchaseOrderTotal' => (int) $unprogrammed->purchase_order_total,
            ],
        ]);
    }
}
