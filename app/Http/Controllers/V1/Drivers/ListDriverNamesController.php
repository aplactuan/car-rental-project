<?php

namespace App\Http\Controllers\V1\Drivers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DriverNameResource;
use App\Repositories\Contracts\DriverRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListDriverNamesController extends Controller
{
    public function __construct(protected DriverRepositoryInterface $driver) {}

    public function __invoke(): AnonymousResourceCollection
    {
        return DriverNameResource::collection($this->driver->names());
    }
}
