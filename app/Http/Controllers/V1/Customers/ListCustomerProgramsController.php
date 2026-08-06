<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProgramResource;
use App\Models\Customer;

class ListCustomerProgramsController extends Controller
{
    public function __invoke(Customer $customer)
    {
        $programs = $customer->programs()->latest()->get();

        return ProgramResource::collection($programs);
    }
}
