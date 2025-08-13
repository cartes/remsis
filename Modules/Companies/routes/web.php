<?php

use Illuminate\Support\Facades\Route;
use Modules\Companies\Http\Controllers\CompaniesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('companies', CompaniesController::class)->names('companies');

    Route::get('companies/{company}/essentials/edit', [CompaniesController::class, 'editEssentials'])
        ->name('companies.essentials.edit');
    Route::put('companies/{company}/essentials', [CompaniesController::class, 'updateEssentials'])
        ->name('companies.essentials.update');
});
