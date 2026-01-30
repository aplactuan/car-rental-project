<?php

namespace App\Repositories\Eloquent;

use App\Models\Car;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\CarRepositoryInterface;

class CarRepository extends BaseRepository implements CarRepositoryInterface
{
    public function __construct(
        Car $model,
        protected BookingRepositoryInterface $bookingRepository
    ) {
        parent::__construct($model);
    }

    public function filter(array $filters)
    {
        $query = $this->model->newQuery();

        if (isset($filters['make'])) {
            $query->where('make', $filters['make']);
        }

        if (isset($filters['model'])) {
            $query->where('model', $filters['model']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['number_of_seats'])) {
            $query->where('number_of_seats', $filters['number_of_seats']);
        }

        return $query->get();
    }

    public function availableInPeriod($startDate, $endDate)
    {
        $bookedCarIds = $this->bookingRepository->getCarIdsBookedInPeriod($startDate, $endDate);

        return $this->model->newQuery()
            ->whereNotIn('id', $bookedCarIds)
            ->get();
    }
}
