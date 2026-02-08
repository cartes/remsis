<?php

use Illuminate\Support\Facades\Route;
use Modules\Companies\Http\Controllers\CompaniesController;

Route::middleware(['auth', 'verified'])->group(function () {
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
    Route::delete('companies/{company}/employees/{user}', [Modules\Companies\Http\Controllers\CompanyEmployeeController::class, 'destroy'])
        ->name('companies.employees.destroy');

    // Detalle de Nómina (para el Modal)
    Route::get('companies/{company}/employees/{employee}/payroll', [Modules\Companies\Http\Controllers\CompanyEmployeeController::class, 'getPayroll'])
        ->name('companies.employees.payroll');
    Route::put('companies/{company}/employees/{employee}/payroll', [Modules\Companies\Http\Controllers\CompanyEmployeeController::class, 'updatePayroll'])
        ->name('companies.employees.payroll.update');
});
