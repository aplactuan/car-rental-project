<?php

use App\Http\Controllers\V1\Availability\ListAvailabilityController;
use App\Http\Controllers\V1\Bills\AddBillController;
use App\Http\Controllers\V1\Bills\DeleteBillController;
use App\Http\Controllers\V1\Bills\ShowBillController;
use App\Http\Controllers\V1\Bills\UpdateBillController;
use App\Http\Controllers\V1\Cars\AddCarController;
use App\Http\Controllers\V1\Cars\ListAvailableCarsController;
use App\Http\Controllers\V1\Cars\SingleCarController;
use App\Http\Controllers\V1\Cars\UpdateCarController;
use App\Http\Controllers\V1\Customers\AddCustomerController;
use App\Http\Controllers\V1\Customers\AddCustomerTransactionController;
use App\Http\Controllers\V1\Customers\DeleteCustomerController;
use App\Http\Controllers\V1\Customers\DeleteCustomerTransactionController;
use App\Http\Controllers\V1\Customers\ListCustomersController;
use App\Http\Controllers\V1\Customers\ListCustomerTransactionsController;
use App\Http\Controllers\V1\Customers\SingleCustomerController;
use App\Http\Controllers\V1\Customers\SingleCustomerTransactionController;
use App\Http\Controllers\V1\Customers\UpdateCustomerController;
use App\Http\Controllers\V1\Customers\UpdateCustomerTransactionController;
use App\Http\Controllers\V1\Drivers\AddDriverController;
use App\Http\Controllers\V1\Drivers\ListDriversController;
use App\Http\Controllers\V1\Drivers\SingleDriverController;
use App\Http\Controllers\V1\Drivers\UpdateDriverController;
use App\Http\Controllers\V1\Transactions\AddBookingController;
use App\Http\Controllers\V1\Transactions\AddTransactionController;
use App\Http\Controllers\V1\Transactions\DeleteBookingController;
use App\Http\Controllers\V1\Transactions\ListBookingsController;
use App\Http\Controllers\V1\Transactions\ListTransactionsController;
use App\Http\Controllers\V1\Transactions\SingleBookingController;
use App\Http\Controllers\V1\Transactions\SingleTransactionController;
use App\Http\Controllers\V1\Transactions\UpdateBookingController;
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

    Route::post('/customers', AddCustomerController::class);
    Route::get('/customers', ListCustomersController::class);
    Route::get('/customers/{customer}', SingleCustomerController::class);
    Route::put('/customers/{customer}', UpdateCustomerController::class);
    Route::delete('/customers/{customer}', DeleteCustomerController::class);

    Route::post('/customers/{customer}/transactions', AddCustomerTransactionController::class);
    Route::get('/customers/{customer}/transactions', ListCustomerTransactionsController::class);
    Route::get('/customers/{customer}/transactions/{transaction}', SingleCustomerTransactionController::class);
    Route::put('/customers/{customer}/transactions/{transaction}', UpdateCustomerTransactionController::class);
    Route::delete('/customers/{customer}/transactions/{transaction}', DeleteCustomerTransactionController::class);

    Route::post('/transactions', AddTransactionController::class);
    Route::get('/transactions', ListTransactionsController::class);
    Route::get('/transactions/{transaction}', SingleTransactionController::class);
    Route::post('/transactions/{transaction}/bill', AddBillController::class);
    Route::get('/transactions/{transaction}/bill', ShowBillController::class);
    Route::patch('/transactions/{transaction}/bill', UpdateBillController::class);
    Route::delete('/transactions/{transaction}/bill', DeleteBillController::class);

    Route::post('/transactions/{transaction}/book', AddBookingController::class);
    Route::get('/transactions/{transaction}/bookings', ListBookingsController::class);
    Route::get('/transactions/{transaction}/bookings/{booking}', SingleBookingController::class);
    Route::put('/transactions/{transaction}/bookings/{booking}', UpdateBookingController::class);
    Route::delete('/transactions/{transaction}/bookings/{booking}', DeleteBookingController::class);

    Route::get('/availability', ListAvailabilityController::class);
});
