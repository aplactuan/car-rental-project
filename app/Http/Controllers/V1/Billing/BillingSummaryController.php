<?php

namespace App\Http\Controllers\V1\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\BillingSummaryRequest;
use App\Http\Resources\V1\BillingSummaryResource;
use App\Repositories\Contracts\BillRepositoryInterface;

class BillingSummaryController extends Controller
{
    public function __construct(protected BillRepositoryInterface $billRepository) {}

    public function __invoke(BillingSummaryRequest $request): BillingSummaryResource
    {
        $summary = $this->billRepository->summarizeForUser(
            $request->user()->id,
            $request->filters()
        );

        return new BillingSummaryResource($summary);
    }
}
