<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\V1\CustomerResource;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Traits\ApiResponses;

class UpdateCustomerController extends Controller
{
    use ApiResponses;

    public function __construct(protected CustomerRepositoryInterface $customer) {}

    public function __invoke(Customer $customer, UpdateCustomerRequest $request)
    {
        $customer = $this->customer->find($customer->id);

        if (! $customer) {
            return $this->error('Customer not found', 404);
        }

        $customer = $this->customer->update($customer->id, $request->validated());

        return new CustomerResource($customer);
    }
}
