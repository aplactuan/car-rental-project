<?php

namespace App\Http\Controllers\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RecentBillResource;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecentBillsController extends Controller
{
    public function __construct(
        protected DashboardRepositoryInterface $dashboardRepository
    ) {}

    public function __invoke(): AnonymousResourceCollection
    {
        return RecentBillResource::collection(
            $this->dashboardRepository->recentBills()
        );
    }
}
