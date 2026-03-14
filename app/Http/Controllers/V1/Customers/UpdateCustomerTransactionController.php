<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Customer;
use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class UpdateCustomerTransactionController extends Controller
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {}

    public function __invoke(
        UpdateTransactionRequest $request,
        Customer $customer,
        Transaction $transaction
    ): TransactionResource {
        $transaction = $this->transactionRepository->updateForUserAndCustomer(
            $transaction->id,
            $request->user()->id,
            $customer->id,
            $request->validated()
        );

        return new TransactionResource($transaction);
    }
}
