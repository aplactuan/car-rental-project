<?php

namespace App\Providers;

use App\Repositories\Contracts\CarRepositoryInterface;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Repositories\Eloquent\CarRepository;
use App\Repositories\Eloquent\DriverRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            CarRepositoryInterface::class,
            CarRepository::class
        );

        $this->app->bind(
            DriverRepositoryInterface::class,
            DriverRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
