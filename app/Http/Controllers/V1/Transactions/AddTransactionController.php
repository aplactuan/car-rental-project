<?php

namespace App\Http\Controllers\V1\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\AddTransactionRequest;
use App\Http\Resources\V1\TransactionResource;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Http\JsonResponse;

class AddTransactionController extends Controller
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {
    }

    public function __invoke(AddTransactionRequest $request): JsonResponse|TransactionResource
    {
        $transaction = $this->transactionRepository->create([
            'user_id' => $request->user()->id,
            'customer_name' => $request->validated('customer_name'),
        ]);

        return (new TransactionResource($transaction))->response()->setStatusCode(201);
    }
}
