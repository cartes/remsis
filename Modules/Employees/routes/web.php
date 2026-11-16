<?php

use Illuminate\Support\Facades\Route;
use Modules\Employees\Http\Controllers\EmployeeHomeController;
use Modules\Employees\Http\Controllers\EmployeesController;
use Modules\Employees\Http\Controllers\EmployeeRequestController;

Route::middleware(['auth'])->group(function () {
    Route::resource('employees', EmployeesController::class)->names('employees');
});

Route::middleware(['web', 'auth', \Modules\Employees\Http\Middleware\UseOwnCompanyContext::class])
    ->prefix('employee')
    ->group(function () {
        Route::get('/profile', [EmployeeHomeController::class, 'show'])
            ->name('employee.profile.show');
        Route::get('/payslips/{payroll}/pdf', [EmployeeHomeController::class, 'payslip'])
            ->whereNumber('payroll')
            ->name('employee.payslips.pdf');
        Route::get('/documents/{document}/download', [EmployeeHomeController::class, 'document'])
            ->whereNumber('document')
            ->name('employee.documents.download');
        Route::post('/vacations', [\Modules\Employees\Http\Controllers\EmployeeVacationController::class, 'store'])
            ->name('employee.vacations.store');
        Route::post('/vacations/{vacationRequest}/cancel', [\Modules\Employees\Http\Controllers\EmployeeVacationController::class, 'cancel'])
            ->whereNumber('vacationRequest')
            ->name('employee.vacations.cancel');
        Route::post('/requests', [EmployeeRequestController::class, 'store'])
            ->name('employee.requests.store');
        Route::post('/requests/{dayRequest}/cancel', [EmployeeRequestController::class, 'cancel'])
            ->whereNumber('dayRequest')
            ->name('employee.requests.cancel');
    });

