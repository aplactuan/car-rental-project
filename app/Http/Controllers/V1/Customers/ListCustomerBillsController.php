<?php

namespace App\Http\Controllers\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ListCustomerBillsRequest;
use App\Http\Resources\V1\BillResource;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Customer;
use App\Repositories\Contracts\BillRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListCustomerBillsController extends Controller
{
    public function __construct(protected BillRepositoryInterface $billRepository) {}

    public function __invoke(ListCustomerBillsRequest $request, Customer $customer): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->filters();
        $includeTransaction = $request->shouldIncludeTransaction();

        $bills = $this->billRepository->paginateForUserAndCustomer(
            $request->user()->id,
            $customer->id,
            $perPage,
            $filters,
        );

        if ($includeTransaction) {
            $bills->load('transaction');
        }

        $collection = BillResource::collection($bills);

        if ($includeTransaction) {
            $included = $bills->getCollection()
                ->pluck('transaction')
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn ($transaction) => (new TransactionResource($transaction))->resolve())
                ->all();

            $collection->additional(['included' => $included]);
        }

        return $collection;
    }
}
