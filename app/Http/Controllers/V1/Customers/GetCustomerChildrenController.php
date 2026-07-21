<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CustomerResource;
use App\Models\Customer;

class GetCustomerChildrenController extends Controller
{
    public function __invoke(Customer $customer)
    {
        $children = $customer->children()->with('parent')->get();

        return CustomerResource::collection($children);
    }
}
