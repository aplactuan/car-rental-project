<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\ListTransactionsRequest;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Customer;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class ListCustomerTransactionsController extends Controller
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {}

    public function __invoke(ListTransactionsRequest $request, Customer $customer)
    {
        $perPage = $request->integer('per_page', 15);
        $transactions = $this->transactionRepository->paginateByUserAndCustomer(
            $request->user()->id,
            $customer->id,
            $perPage
        );

        return TransactionResource::collection($transactions);
    }
}
