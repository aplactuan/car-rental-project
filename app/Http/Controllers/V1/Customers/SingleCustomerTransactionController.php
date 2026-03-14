<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Customer;
use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Http\Request;

class SingleCustomerTransactionController extends Controller
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {}

    public function __invoke(Request $request, Customer $customer, Transaction $transaction): TransactionResource
    {
        $transaction = $this->transactionRepository->findForUserAndCustomer(
            $transaction->id,
            $request->user()->id,
            $customer->id
        );

        return new TransactionResource($transaction);
    }
}
