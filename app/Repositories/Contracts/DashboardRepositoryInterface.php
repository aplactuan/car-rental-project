<?php

namespace App\Repositories\Contracts;

use App\Models\Customer;
use App\Models\Program;
use App\Models\TripReport;
use Illuminate\Database\Eloquent\Collection;

interface DashboardRepositoryInterface
{
    /**
     * @return array{
     *     top_customers: Collection<int, Customer>,
     *     total_programs: int,
     *     total_purchase_order_amount: int,
     *     total_billed: int,
     *     total_paid: int,
     *     balance_to_collect: int
     * }
     */
    public function customerOverview(): array;

    /**
     * @return array{
     *     top_programs: Collection<int, Program>,
     *     top_billed_programs: Collection<int, Program>,
     *     top_paid_programs: Collection<int, Program>
     * }
     */
    public function programRankings(): array;

    /**
     * @return Collection<int, TripReport>
     */
    public function recentTripReports(): Collection;

    /**
     * @return list<array{
     *     month: string,
     *     total_billed: int,
     *     total_collectible: int,
     *     total_paid: int
     * }>
     */
    public function monthReport(): array;
}
