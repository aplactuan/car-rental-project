<?php

namespace App\Http\Controllers\V1\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\ListTransactionsRequest;
use App\Http\Resources\V1\TransactionResource;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class ListTransactionsController extends Controller
{
    public function __construct(protected TransactionRepositoryInterface $transactionRepository)
    {
    }

    public function __invoke(ListTransactionsRequest $request)
    {
        $perPage = $request->input('per_page', 15);
        $transactions = $this->transactionRepository->paginateByUser($request->user()->id, $perPage);

        return TransactionResource::collection($transactions);
    }
}
