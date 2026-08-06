<?php

namespace App\Repositories\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DriverRepositoryInterface
{
    public function all();

    /**
     * All drivers with id and name columns only, ordered by last then first name.
     */
    public function names();

    /**
     * @param  array{filter?: ?string}  $filters
     */
    public function paginate(int $perPage = 15, array $filters = []);

    public function find($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);

    /**
     * Drivers available in the given period (no overlapping schedules).
     *
     * @param  CarbonInterface|string  $startDate
     * @param  CarbonInterface|string  $endDate
     * @return LengthAwarePaginator
     */
    /**
     * @param  array{filter?: ?string}  $filters
     */
    public function availableInPeriod($startDate, $endDate, int $perPage = 15, array $filters = []);
}
