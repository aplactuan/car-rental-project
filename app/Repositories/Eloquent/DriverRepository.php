<?php

namespace App\Repositories\Eloquent;

use App\Models\Driver;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\DriverRepositoryInterface;

class DriverRepository extends BaseRepository implements DriverRepositoryInterface
{
    public function __construct(
        Driver $model,
        protected BookingRepositoryInterface $bookingRepository
    ) {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model->paginate($perPage);
    }

    public function availableInPeriod($startDate, $endDate, int $perPage = 15)
    {
        $bookedDriverIds = $this->bookingRepository->getDriverIdsBookedInPeriod($startDate, $endDate);

        return $this->model->newQuery()
            ->whereNotIn('id', $bookedDriverIds)
            ->paginate($perPage);
    }
}

