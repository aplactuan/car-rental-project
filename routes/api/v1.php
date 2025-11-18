<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/cars', function (\Illuminate\Http\Client\Request $request) {
        return response(['status' => 'ok'], 200);
    });
});

