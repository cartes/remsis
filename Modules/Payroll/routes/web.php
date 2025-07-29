<?php

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Http\Controllers\PayrollController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('payrolls', PayrollController::class)->names('payroll');
});
