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

    public function paginate(int $perPage = 15, array $filters = [])
    {
        return $this->applyFilters($this->model->newQuery(), $filters)->paginate($perPage);
    }

    public function availableInPeriod($startDate, $endDate, int $perPage = 15, array $filters = [])
    {
        $scheduledDriverIds = $this->scheduleRepository->getDriverIdsScheduledInPeriod($startDate, $endDate);

        return $this->applyFilters($this->model->newQuery(), $filters)
            ->whereNotIn('id', $scheduledDriverIds)
            ->paginate($perPage);
    }

    protected function applyFilters($query, array $filters)
    {
        return $this->applyNameFilter($query, $filters['filter'] ?? null);
    }

    protected function applyNameFilter($query, ?string $filter)
    {
        if ($filter === null || trim($filter) === '') {
            return $query;
        }

        $normalizedFilter = mb_strtolower(trim($filter));

        return $query->where(function ($driverQuery) use ($normalizedFilter) {
            $driverQuery
                ->whereRaw('LOWER(first_name) LIKE ?', ["%{$normalizedFilter}%"])
                ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$normalizedFilter}%"]);
        });
    }
}
