<?php

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Http\Controllers\PayrollController;
use Modules\Payroll\Http\Controllers\PayrollPeriodController;

Route::middleware(['auth'])->group(function () {
    Route::resource('payrolls', PayrollController::class)->names('payroll');
    Route::get('/payrolls/company/{company?}', [PayrollController::class, 'index'])->name('payrolls.byCompany');
});
