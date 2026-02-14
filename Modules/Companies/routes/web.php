<?php

use Illuminate\Support\Facades\Route;
use Modules\Companies\Http\Controllers\CompaniesController;

Route::middleware(['auth'])->group(function () {
    Route::resource('companies', CompaniesController::class)->names('companies');

    Route::get('companies/{company}/essentials/edit', [CompaniesController::class, 'editEssentials'])
        ->name('companies.essentials.edit');
    Route::put('companies/{company}/essentials', [CompaniesController::class, 'updateEssentials'])
        ->name('companies.essentials.update');

    Route::get('companies/{company}/employees', [CompaniesController::class, 'employees'])
        ->name('companies.employees');

    // Gestión de Empleados (Nómina) dentro de Empresa
    Route::post('companies/{company}/employees', [Modules\Companies\Http\Controllers\CompanyEmployeeController::class, 'store'])
        ->name('companies.employees.store');
    Route::get('companies/{company}/employees/search', [Modules\Companies\Http\Controllers\CompanyEmployeeController::class, 'search'])
        ->name('companies.employees.search');
    Route::delete('companies/{company}/employees/{user}', [Modules\Companies\Http\Controllers\CompanyEmployeeController::class, 'destroy'])
        ->name('companies.employees.destroy');

    // Detalle de Nómina (para el Modal)
    Route::get('companies/{company}/employees/{employee}/payroll', [Modules\Companies\Http\Controllers\CompanyEmployeeController::class, 'getPayroll'])
        ->name('companies.employees.payroll');
    Route::put('companies/{company}/employees/{employee}/payroll', [Modules\Companies\Http\Controllers\CompanyEmployeeController::class, 'updatePayroll'])
        ->name('companies.employees.payroll.update');

    // Centros de Costo
    Route::get('companies/{company}/cost-centers', [Modules\Companies\Http\Controllers\CostCenterController::class, 'index'])
        ->name('companies.cost-centers');
    Route::post('companies/{company}/cost-centers', [Modules\Companies\Http\Controllers\CostCenterController::class, 'store'])
        ->name('companies.cost-centers.store');
    Route::put('companies/{company}/cost-centers/{costCenter}', [Modules\Companies\Http\Controllers\CostCenterController::class, 'update'])
        ->name('companies.cost-centers.update');
    Route::delete('companies/{company}/cost-centers/{costCenter}', [Modules\Companies\Http\Controllers\CostCenterController::class, 'destroy'])
        ->name('companies.cost-centers.destroy');

    Route::get('companies/{company}/dashboard', [CompaniesController::class, 'dashboard'])
        ->name('companies.dashboard');

    Route::get('companies/{company}/transactions', [CompaniesController::class, 'transactions'])
        ->name('companies.transactions');

    // Payroll Periods nested under companies
    Route::prefix('companies/{company}/payroll-periods')->name('companies.payroll-periods.')->group(function () {
        Route::get('/', [Modules\Payroll\Http\Controllers\PayrollPeriodController::class, 'index'])->name('index');
        Route::get('/create', [Modules\Payroll\Http\Controllers\PayrollPeriodController::class, 'create'])->name('create');
        Route::post('/', [Modules\Payroll\Http\Controllers\PayrollPeriodController::class, 'store'])->name('store');
        Route::get('/{period}/wizard', [Modules\Payroll\Http\Controllers\PayrollPeriodController::class, 'wizard'])->name('wizard');
        Route::post('/{period}/calculate', [Modules\Payroll\Http\Controllers\PayrollPeriodController::class, 'calculate'])->name('calculate');
        Route::put('/{period}/lines/{line}', [Modules\Payroll\Http\Controllers\PayrollPeriodController::class, 'updateLine'])->name('update-line');
        Route::post('/{period}/update-status', [Modules\Payroll\Http\Controllers\PayrollPeriodController::class, 'updateStatus'])->name('update-status'); // For closing
        Route::patch('/{id}/status', [Modules\Payroll\Http\Controllers\PayrollPeriodController::class, 'updateStatus'])->name('updateStatus');
    });
});
