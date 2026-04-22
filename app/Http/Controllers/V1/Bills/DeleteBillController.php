<?php

namespace App\Http\Controllers\V1\Bills;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Repositories\Contracts\BillRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteBillController extends Controller
{
    public function __construct(protected BillRepositoryInterface $billRepository) {}

    public function __invoke(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        $bill = $this->billRepository->findByTransaction($transaction->id);

        if ($bill->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft bills can be deleted.',
            ], 422);
        }

        $bill->delete();

        return response()->json(null, 204);
    }
}
