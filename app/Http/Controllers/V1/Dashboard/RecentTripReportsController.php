<?php

namespace App\Http\Controllers\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RecentTripReportResource;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecentTripReportsController extends Controller
{
    public function __construct(
        protected DashboardRepositoryInterface $dashboardRepository
    ) {}

    public function __invoke(): AnonymousResourceCollection
    {
        return RecentTripReportResource::collection(
            $this->dashboardRepository->recentTripReports()
        );
    }
}
