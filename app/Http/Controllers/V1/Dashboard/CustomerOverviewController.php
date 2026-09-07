<?php

namespace App\Http\Controllers\V1\Dashboard;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CustomerOverviewResource;
use App\Models\Customer;
use App\Models\Program;
use App\Models\TripReport;
use Illuminate\Database\Eloquent\Builder;

class CustomerOverviewController extends Controller
{
    public function __invoke(): CustomerOverviewResource
    {
        $topCustomers = Customer::query()
            ->has('purchaseOrders')
            ->withCount('purchaseOrders')
            ->withSum('purchaseOrders', 'amount')
            ->orderByDesc('purchase_orders_sum_amount')
            ->orderBy('name')
            ->orderBy('id')
            ->limit(5)
            ->get();

        $balanceToCollect = TripReport::query()
            ->whereHas('invoice', function (Builder $query): void {
                $query->where('status', InvoiceStatus::Unpaid);
            })
            ->sum('amount');

        return new CustomerOverviewResource([
            'top_customers' => $topCustomers,
            'total_programs' => Program::query()->count(),
            'balance_to_collect' => $balanceToCollect,
        ]);
    }
}
