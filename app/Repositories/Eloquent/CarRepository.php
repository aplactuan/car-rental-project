<?php

namespace App\Repositories\Eloquent;

use App\Models\Car;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\CarRepositoryInterface;

class CarRepository extends BaseRepository implements CarRepositoryInterface
{
    public function __construct(Car $model)
    {
        $this->model = $model;
    }
}
