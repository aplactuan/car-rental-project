<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CustomerResource;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Traits\ApiResponses;

class SingleCustomerController extends Controller
{
    use ApiResponses;

    public function __construct(protected CustomerRepositoryInterface $customer) {}

    public function __invoke(Customer $customer)
    {
        $customer = $this->customer->find($customer->id);

        if (! $customer) {
            return $this->error('Customer not found', 404);
        }

        return new CustomerResource($customer);
    }
}
