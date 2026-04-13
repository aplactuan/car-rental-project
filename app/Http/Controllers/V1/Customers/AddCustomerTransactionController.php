<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\AddCustomerTransactionRequest;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Customer;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Http\JsonResponse;

class AddCustomerTransactionController extends Controller
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {}

    public function __invoke(AddCustomerTransactionRequest $request, Customer $customer): JsonResponse|TransactionResource
    {
        $transaction = $this->transactionRepository->create([
            'user_id' => $request->user()->id,
            'customer_id' => $customer->id,
            'name' => $request->validated('name'),
        ]);

        return (new TransactionResource($transaction))->response()->setStatusCode(201);
    }
}
