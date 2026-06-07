<?php

namespace App\Http\Controllers\V1\BillPayments;

use App\Enums\BillStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BillPayment\AddBillPaymentRequest;
use App\Http\Resources\V1\BillPaymentResource;
use App\Models\Transaction;
use App\Repositories\Contracts\BillPaymentRepositoryInterface;
use App\Repositories\Contracts\BillRepositoryInterface;
use Illuminate\Http\JsonResponse;

class AddBillPaymentController extends Controller
{
    public function __construct(
        protected BillRepositoryInterface $billRepository,
        protected BillPaymentRepositoryInterface $billPaymentRepository,
    ) {}

    public function __invoke(AddBillPaymentRequest $request, Transaction $transaction): JsonResponse|BillPaymentResource
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        $bill = $this->billRepository->findByTransaction($transaction->id);

        if (! in_array($bill->status, [BillStatus::Issued->value, BillStatus::PartiallyPaid->value], true)) {
            return response()->json([
                'message' => "A payment cannot be recorded for a bill in {$bill->status} status.",
            ], 422);
        }

        $payment = $this->billPaymentRepository->create($bill, [
            'amount' => $request->validated('amount'),
            'method' => $request->validated('method'),
            'reference_number' => $request->validated('reference_number'),
            'notes' => $request->validated('notes'),
        ], $request->file('proof_image'));

        return (new BillPaymentResource($payment))->response()->setStatusCode(201);
    }
}
