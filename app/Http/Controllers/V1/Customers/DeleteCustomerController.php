<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Http\JsonResponse;

class DeleteCustomerController extends Controller
{
    public function __construct(protected CustomerRepositoryInterface $customer) {}

    public function __invoke(Customer $customer): JsonResponse
    {
        $this->customer->delete($customer->id);

        return response()->json(null, 204);
    }
}
