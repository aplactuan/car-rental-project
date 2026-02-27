<?php

namespace App\Repositories\Eloquent;

use App\Models\Driver;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Repositories\Contracts\ScheduleRepositoryInterface;

class DriverRepository extends BaseRepository implements DriverRepositoryInterface
{
    public function __construct(
        Driver $model,
        protected ScheduleRepositoryInterface $scheduleRepository
    ) {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model->paginate($perPage);
    }

    public function availableInPeriod($startDate, $endDate, int $perPage = 15)
    {
        $scheduledDriverIds = $this->scheduleRepository->getDriverIdsScheduledInPeriod($startDate, $endDate);

        return $this->model->newQuery()
            ->whereNotIn('id', $scheduledDriverIds)
            ->paginate($perPage);
    }
}
