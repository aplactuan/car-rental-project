<?php

namespace App\Repositories\Eloquent;

use App\Models\Driver;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\DriverRepositoryInterface;

class DriverRepository extends BaseRepository implements DriverRepositoryInterface
{
    public function __construct(Driver $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model->paginate($perPage);
    }
}

