<?php

namespace App\Http\Controllers\V1\Customers;

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
            ->findOrFail($customer->id);

        return new CustomerResource($customer);
    }
}
