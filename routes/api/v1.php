<?php

use App\Http\Controllers\V1\Cars\AddCarController;
use App\Http\Controllers\V1\Cars\ListAvailableCarsController;
use App\Http\Controllers\V1\Cars\SingleCarController;
use App\Http\Controllers\V1\Cars\UpdateCarController;
use App\Http\Controllers\V1\Drivers\AddDriverController;
use App\Http\Controllers\V1\Drivers\ListDriversController;
use App\Http\Controllers\V1\Drivers\SingleDriverController;
use App\Http\Controllers\V1\Drivers\UpdateDriverController;
use App\Http\Controllers\V1\Transactions\AddTransactionController;
use App\Http\Controllers\V1\Availability\ListAvailabilityController;
use App\Http\Controllers\V1\Transactions\ListTransactionsController;
use App\Http\Controllers\V1\Transactions\SingleTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/cars', AddCarController::class);
    Route::get('/cars', ListAvailableCarsController::class);
    Route::get('/cars/{car}', SingleCarController::class);
    Route::put('/cars/{car}', UpdateCarController::class);

    Route::post('/drivers', AddDriverController::class);
    Route::get('/drivers', ListDriversController::class);
    Route::get('/drivers/{driver}', SingleDriverController::class);
    Route::put('/drivers/{driver}', UpdateDriverController::class);

    Route::post('/transactions', AddTransactionController::class);
    Route::get('/transactions', ListTransactionsController::class);
    Route::get('/transactions/{transaction}', SingleTransactionController::class);

    Route::get('/availability', ListAvailabilityController::class);
});
