<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ListCustomersRequest;
use App\Http\Resources\V1\CustomerResource;
use App\Repositories\Contracts\CustomerRepositoryInterface;

class ListCustomersController extends Controller
{
    public function __construct(protected CustomerRepositoryInterface $customer) {}

    public function __invoke(ListCustomersRequest $request)
    {
        $perPage = $request->input('per_page', 15);
        $customers = $this->customer->paginate($perPage);

        return CustomerResource::collection($customers);
    }
}
