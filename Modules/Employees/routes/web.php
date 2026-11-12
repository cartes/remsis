<?php

use Illuminate\Support\Facades\Route;
use Modules\Employees\Http\Controllers\EmployeeHomeController;
use Modules\Employees\Http\Controllers\EmployeesController;
use Modules\Employees\Http\Controllers\EmployeeRequestController;

Route::middleware(['auth'])->group(function () {
    Route::resource('employees', EmployeesController::class)->names('employees');
});

Route::middleware(['web', 'auth'])
    ->prefix('employee')
    ->group(function () {
        Route::get('/profile', [EmployeeHomeController::class, 'show'])
            ->name('employee.profile.show');
        Route::post('/requests', [EmployeeRequestController::class, 'store'])
            ->name('employee.requests.store');
        Route::post('/requests/{dayRequest}/cancel', [EmployeeRequestController::class, 'cancel'])
            ->name('employee.requests.cancel');
    });

