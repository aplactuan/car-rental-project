<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Customer;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddCustomerTransactionController extends Controller
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {}

    public function __invoke(Request $request, Customer $customer): JsonResponse|TransactionResource
    {
        $transaction = $this->transactionRepository->create([
            'user_id' => $request->user()->id,
            'customer_id' => $customer->id,
        ]);

        return (new TransactionResource($transaction))->response()->setStatusCode(201);
    }
}
