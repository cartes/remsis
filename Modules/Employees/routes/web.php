<?php

use Illuminate\Support\Facades\Route;
use Modules\Employees\Http\Controllers\EmployeesController;
use Modules\Employees\Http\Controllers\EmployeeHomeController;

Route::middleware(['auth'])->group(function () {
    Route::resource('employees', EmployeesController::class)->names('employees');
});

Route::middleware(['web', 'auth', 'role:employee'])
    ->prefix('employee')
    ->group(function () {
        Route::get('/profile', [EmployeeHomeController::class, 'show'])
            ->name('employee.profile.show');
    });
