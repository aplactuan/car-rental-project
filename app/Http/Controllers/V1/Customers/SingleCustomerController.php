<?php

namespace App\Http\Controllers\V1\Customers;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CustomerResource;
use App\Models\Customer;

class SingleCustomerController extends Controller
{
    public function __invoke(Customer $customer)
    {
        $customer = Customer::query()
            ->with('parent')
            ->withCount('purchaseOrders')
            ->withSum('purchaseOrders', 'amount')
            ->withCount(['purchaseOrders as unprogrammed_purchase_orders_count' => fn ($query) => $query->whereNull('program_id')])
            ->withSum(['purchaseOrders as unprogrammed_purchase_orders_sum_amount' => fn ($query) => $query->whereNull('program_id')], 'amount')
            ->withCount(['programs as programs_count' => fn ($query) => $query->has('purchaseOrders')])
            ->withCount('tripReports')
            ->withCount(['tripReports as unattached_trip_reports_count' => fn ($query) => $query->whereNull('invoice_id')])
            ->withSum(['tripReports as unpaid_invoices_sum_amount' => function ($query): void {
                $query->whereHas('invoice', function ($invoice): void {
                    $invoice->where('status', InvoiceStatus::Unpaid);
                });
            }], 'amount')
            ->findOrFail($customer->id);

        return new CustomerResource($customer);
    }
}
