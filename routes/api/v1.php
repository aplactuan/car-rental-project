<?php

use App\Http\Controllers\V1\Cars\AddCarController;
use App\Http\Controllers\V1\Cars\ListAvailableCarsController;
use App\Http\Controllers\V1\Cars\UpdateCarController;
use App\Http\Controllers\V1\Drivers\AddDriverController;
use Illuminate\Support\Facades\Route;

#Route::prefix('v1')->group(function () {
#    Route::post('/cars', AddCarController::class);
#})->middleware('auth:sanctum');

Route::prefix('v1')
    ->group(function () {
        Route::post('/cars', AddCarController::class)->middleware('auth:sanctum');
        Route::get('/cars', ListAvailableCarsController::class)->middleware('auth:sanctum');
        Route::put('/cars/{car}', UpdateCarController::class)->middleware('auth:sanctum');

        Route::post('/drivers', AddDriverController::class)->middleware('auth:sanctum');
    });
