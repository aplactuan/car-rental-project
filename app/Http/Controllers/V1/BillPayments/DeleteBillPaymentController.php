<?php

namespace App\Http\Controllers\V1\BillPayments;

use App\Http\Controllers\Controller;
use App\Models\BillPayment;
use App\Models\Transaction;
use App\Repositories\Contracts\BillPaymentRepositoryInterface;
use App\Repositories\Contracts\BillRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteBillPaymentController extends Controller
{
    public function __construct(
        protected BillRepositoryInterface $billRepository,
        protected BillPaymentRepositoryInterface $billPaymentRepository,
    ) {}

    public function __invoke(Request $request, Transaction $transaction, BillPayment $payment): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        $bill = $this->billRepository->findByTransaction($transaction->id);

        if ($payment->bill_id !== $bill->id) {
            abort(404);
        }

        $this->billPaymentRepository->delete($payment);

        return response()->json(null, 204);
    }
}
