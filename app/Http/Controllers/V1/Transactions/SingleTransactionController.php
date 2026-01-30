<?php

namespace App\Http\Controllers\V1\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Http\Request;

class SingleTransactionController extends Controller
{
    public function __construct(protected TransactionRepositoryInterface $transactionRepository)
    {
    }

    public function __invoke(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        $transaction = $this->transactionRepository->find($transaction->id);

        return new TransactionResource($transaction);
    }
}
