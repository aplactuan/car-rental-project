<?php

namespace App\Repositories\Contracts;

use Carbon\CarbonInterface;

interface DriverRepositoryInterface
{
    public function all();

    public function paginate(int $perPage = 15);

    public function find($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);

    /**
     * Drivers available in the given period (no overlapping schedules).
     *
     * @param  CarbonInterface|string  $startDate
     * @param  CarbonInterface|string  $endDate
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function availableInPeriod($startDate, $endDate, int $perPage = 15);
}
