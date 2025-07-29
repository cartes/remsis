<?php

use Illuminate\Support\Facades\Route;
use Modules\Employees\Http\Controllers\EmployeesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('employees', EmployeesController::class)->names('employees');
});
