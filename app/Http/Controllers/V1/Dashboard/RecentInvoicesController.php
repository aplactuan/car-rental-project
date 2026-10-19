<?php

namespace App\Http\Controllers\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RecentInvoiceResource;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecentInvoicesController extends Controller
{
    public function __construct(
        protected DashboardRepositoryInterface $dashboardRepository
    ) {}

    public function __invoke(): AnonymousResourceCollection
    {
        return RecentInvoiceResource::collection(
            $this->dashboardRepository->recentInvoices()
        );
    }
}
