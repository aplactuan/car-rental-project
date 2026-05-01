<?php

namespace App\Repositories\Contracts;

use App\Models\Car;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface CarRepositoryInterface
{
    public function all();

    public function find($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);

    public function filter(array $filters);

    public function paginate(array $filters, int $perPage = 15);

    /**
     * Cars available in the given period (no overlapping bookings).
     *
     * @param  CarbonInterface|string  $startDate
     * @param  CarbonInterface|string  $endDate
     * @return Collection<int, Car>
     */
    public function availableInPeriod($startDate, $endDate);
}
