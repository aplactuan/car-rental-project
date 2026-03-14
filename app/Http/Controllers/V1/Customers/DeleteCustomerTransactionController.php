<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteCustomerTransactionController extends Controller
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {}

    public function __invoke(Request $request, Customer $customer, Transaction $transaction): JsonResponse
    {
        $this->transactionRepository->deleteForUserAndCustomer(
            $transaction->id,
            $request->user()->id,
            $customer->id
        );

        return response()->json(null, 204);
    }
}
