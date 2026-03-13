<?php

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Http\Controllers\PayrollController;
use Modules\Payroll\Http\Controllers\FreelancerController;
use Modules\Payroll\Http\Controllers\FreelancerReceiptController;
use Modules\Payroll\Http\Controllers\PayrollPeriodController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth'])->scopeBindings()->group(function () {
    Route::group([], function () {
        Route::resource('payroll', PayrollController::class)->names('payroll');
    });
    
    // Freelancers / Honorarios under companies
    Route::prefix('companies/{company}/honorarios')->name('companies.freelancers.')->group(function () {
        // CRUD de Freelancers
        Route::get('/', [FreelancerController::class, 'index'])->name('index');
        Route::post('/', [FreelancerController::class, 'store'])->name('store');
        Route::get('/search', [FreelancerController::class, 'search'])->name('search');
        Route::get('/{freelancer}', [FreelancerController::class, 'show'])->name('show');
        Route::put('/{freelancer}', [FreelancerController::class, 'update'])->name('update');
        Route::delete('/{freelancer}', [FreelancerController::class, 'destroy'])->name('destroy');
        
        // CRUD de Boletas
        Route::post('/{freelancer}/receipts', [FreelancerReceiptController::class, 'store'])->name('receipts.store');
        Route::put('/{freelancer}/receipts/{receipt}', [FreelancerReceiptController::class, 'update'])->name('receipts.update');
        Route::delete('/{freelancer}/receipts/{receipt}', [FreelancerReceiptController::class, 'destroy'])->name('receipts.destroy');
    });
    Route::get('/payrolls/company/{company?}', [PayrollController::class, 'index'])->name('payrolls.byCompany');
});
