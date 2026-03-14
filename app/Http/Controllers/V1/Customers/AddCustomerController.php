<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\AddCustomerRequest;
use App\Http\Resources\V1\CustomerResource;
use App\Repositories\Contracts\CustomerRepositoryInterface;

class AddCustomerController extends Controller
{
    public function __construct(protected CustomerRepositoryInterface $customer) {}

    public function __invoke(AddCustomerRequest $request)
    {
        $customer = $this->customer->create($request->validated());

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }
}
