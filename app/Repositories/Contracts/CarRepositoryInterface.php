<?php

namespace App\Repositories\Contracts;

use Carbon\CarbonInterface;

interface CarRepositoryInterface
{
    public function all();

    public function find($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);

    public function filter(array $filters);

    /**
     * Cars available in the given period (no overlapping bookings).
     *
     * @param  CarbonInterface|string  $startDate
     * @param  CarbonInterface|string  $endDate
     * @return \Illuminate\Support\Collection<int, \App\Models\Car>
     */
    public function availableInPeriod($startDate, $endDate);
}
