<?php

namespace App\Http\Controllers\V1\BillPayments;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BillPaymentResource;
use App\Models\Transaction;
use App\Repositories\Contracts\BillPaymentRepositoryInterface;
use App\Repositories\Contracts\BillRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListBillPaymentsController extends Controller
{
    public function __construct(
        protected BillRepositoryInterface $billRepository,
        protected BillPaymentRepositoryInterface $billPaymentRepository,
    ) {}

    public function __invoke(Request $request, Transaction $transaction): AnonymousResourceCollection
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        $bill = $this->billRepository->findByTransaction($transaction->id);

        return BillPaymentResource::collection($this->billPaymentRepository->listForBill($bill));
    }
}
