<?php

namespace App\Http\Controllers\V1\Bills;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bill\AddBillRequest;
use App\Http\Resources\V1\BillResource;
use App\Models\Transaction;
use App\Repositories\Contracts\BillRepositoryInterface;
use Illuminate\Http\JsonResponse;

class AddBillController extends Controller
{
    public function __construct(protected BillRepositoryInterface $billRepository) {}

    public function __invoke(AddBillRequest $request, Transaction $transaction): JsonResponse|BillResource
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        if ($transaction->bill()->exists()) {
            return response()->json([
                'message' => 'A bill already exists for this transaction.',
            ], 409);
        }

        $bill = $this->billRepository->create([
            'transaction_id' => $transaction->id,
            'amount' => $request->validated('amount'),
            'notes' => $request->validated('notes'),
            'due_at' => $request->validated('due_at'),
        ]);

        return (new BillResource($bill))->response()->setStatusCode(201);
    }
}
