<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;




Route::controller(TransactionController::class)->group(function () {

    Route::get('/summary', 'summary');

    Route::get('/incomes', 'incomes');

    Route::get('/expenses', 'expenses');
});

Route::apiResource('transactions', TransactionController::class);

Route::post('login', [AdminController::class, 'login']);

Route::prefix('admin')->group(function () {
    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminController::class, 'logout']);
    });
});
