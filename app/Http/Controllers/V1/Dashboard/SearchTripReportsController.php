<?php

namespace App\Http\Controllers\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\SearchTripReportsRequest;
use App\Http\Resources\V1\RecentTripReportResource;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchTripReportsController extends Controller
{
    public function __construct(
        protected DashboardRepositoryInterface $dashboardRepository
    ) {}

    public function __invoke(SearchTripReportsRequest $request): AnonymousResourceCollection
    {
        return RecentTripReportResource::collection(
            $this->dashboardRepository->searchTripReports($request->queryString())
        );
    }
}
