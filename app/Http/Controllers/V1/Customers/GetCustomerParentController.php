<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CustomerResource;
use App\Models\Customer;
use App\Traits\ApiResponses;

class GetCustomerParentController extends Controller
{
    use ApiResponses;

    public function __invoke(Customer $customer)
    {
        $parent = $customer->parent()->with('parent')->first();

        if (! $parent) {
            return $this->error('Customer has no parent account', 404);
        }

        return new CustomerResource($parent);
    }
}
