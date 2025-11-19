<?php

use App\Http\Controllers\V1\Cars\AddCarController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/cars', AddCarController::class);
});
