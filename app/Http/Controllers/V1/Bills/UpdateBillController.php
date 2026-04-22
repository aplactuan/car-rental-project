<?php

namespace App\Http\Controllers\V1\Bills;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bill\UpdateBillRequest;
use App\Http\Resources\V1\BillResource;
use App\Models\Transaction;
use App\Repositories\Contracts\BillRepositoryInterface;
use Illuminate\Http\JsonResponse;

class UpdateBillController extends Controller
{
    public function __construct(protected BillRepositoryInterface $billRepository) {}

    public function __invoke(UpdateBillRequest $request, Transaction $transaction): BillResource|JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        $bill = $this->billRepository->findByTransaction($transaction->id);
        $data = $request->validated();
        $nextStatus = $data['status'] ?? $bill->status;

        if (in_array($bill->status, ['paid', 'cancelled'], true)) {
            return response()->json([
                'message' => "Bill in {$bill->status} status cannot be updated.",
            ], 422);
        }

        if (! $this->canTransition($bill->status, $nextStatus)) {
            return response()->json([
                'message' => "Invalid status transition from {$bill->status} to {$nextStatus}.",
            ], 422);
        }

        if ($bill->status !== 'issued' && $nextStatus === 'issued') {
            $data['issued_at'] = now();
        }

        if ($bill->status !== 'paid' && $nextStatus === 'paid') {
            $data['paid_at'] = now();
        }

        $bill = $this->billRepository->update($bill, $data);

        return new BillResource($bill);
    }

    private function canTransition(string $current, string $next): bool
    {
        if ($current === $next) {
            return true;
        }

        return match ($current) {
            'draft' => in_array($next, ['issued', 'cancelled'], true),
            'issued' => in_array($next, ['paid', 'cancelled'], true),
            default => false,
        };
    }
}
