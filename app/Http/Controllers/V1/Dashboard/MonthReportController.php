<?php

namespace App\Http\Controllers\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MonthReportResource;
use App\Repositories\Contracts\DashboardRepositoryInterface;

class MonthReportController extends Controller
{
    public function __construct(
        protected DashboardRepositoryInterface $dashboardRepository
    ) {}

    public function __invoke(): MonthReportResource
    {
        return new MonthReportResource(
            $this->dashboardRepository->monthReport()
        );
    }
}
