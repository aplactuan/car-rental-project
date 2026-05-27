<?php

namespace App\Http\Controllers\V1\Bills;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\InvoiceResource;
use App\Models\Transaction;
use App\Repositories\Contracts\BillRepositoryInterface;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(protected BillRepositoryInterface $billRepository) {}

    public function __invoke(Request $request, Transaction $transaction): InvoiceResource
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(404);
        }

        $bill = $this->billRepository->findByTransactionWithDetails($transaction->id);

        return new InvoiceResource($bill);
    }
}
