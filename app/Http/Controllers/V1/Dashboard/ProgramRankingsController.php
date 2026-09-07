<?php

namespace App\Http\Controllers\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProgramRankingsResource;
use App\Repositories\Contracts\DashboardRepositoryInterface;

class ProgramRankingsController extends Controller
{
    public function __construct(
        protected DashboardRepositoryInterface $dashboardRepository
    ) {}

    public function __invoke(): ProgramRankingsResource
    {
        return new ProgramRankingsResource(
            $this->dashboardRepository->programRankings()
        );
    }
}
