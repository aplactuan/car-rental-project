<?php

namespace App\Repositories\Eloquent;

use App\Models\Car;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\CarRepositoryInterface;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class CarRepository extends BaseRepository implements CarRepositoryInterface
{
    public function __construct(
        Car $model,
        protected ScheduleRepositoryInterface $scheduleRepository
    ) {
        parent::__construct($model);
    }

    public function filter(array $filters)
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);

        return $query->get();
    }

    public function paginate(array $filters, int $perPage = 15)
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function availableInPeriod($startDate, $endDate)
    {
        $scheduledCarIds = $this->scheduleRepository->getCarIdsScheduledInPeriod($startDate, $endDate);

        return $this->model->newQuery()
            ->whereNotIn('id', $scheduledCarIds)
            ->get();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['door'])) {
            $query->where('door', $filters['door']);
        }

        if (isset($filters['seats'])) {
            $query->where('seats', $filters['seats']);
        }

        if (isset($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        if (isset($filters['color'])) {
            $query->where('color', $filters['color']);
        }

        if (isset($filters['make'])) {
            $query->where('make', $filters['make']);
        }

        if (isset($filters['model'])) {
            $query->where('model', $filters['model']);
        }

        if (isset($filters['plate_number'])) {
            $query->where('plate_number', $filters['plate_number']);
        }
    }
}
