<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminPanel\Http\Controllers\Auth\AuthenticatedSessionController;
use Modules\AdminPanel\Http\Controllers\AdminPanelController;
use Modules\AdminPanel\Http\Controllers\Auth\LogoutController;
use Modules\AdminPanel\Http\Controllers\SettingsController;
use Modules\AdminPanel\Http\Controllers\CompaniesApiController;


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {
    Route::post('logout', [AdminPanelController::class, 'logout'])->name('admin.logout');
});

Route::middleware(['web', 'auth', 'role:super-admin|admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminPanelController::class, 'index'])->name('admin.dashboard');
});

Route::prefix('settings')
    ->middleware(['web', 'auth', 'blockEmployeeOnAdmin', 'role:super-admin|admin'])
    ->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/afps', [SettingsController::class, 'storeAfp'])->name('afps.store');
        Route::post('/isapres', [SettingsController::class, 'storeIsapre'])->name('isapres.store');
        Route::post('/ccafs', [SettingsController::class, 'storeCcaf'])->name('ccafs.store');

        Route::get('/afps/{afp}/edit', [SettingsController::class, 'editAfp'])->name('afps.edit');
        Route::delete('/afps/{afp}', [SettingsController::class, 'destroyAfp'])->name('afps.destroy');

        Route::post('/bancos', [SettingsController::class, 'storeBanco'])->name('bancos.store');
        Route::get('/bancos/{banco}/edit', [SettingsController::class, 'editBanco'])->name('bancos.edit');
        Route::put('/bancos/{banco}', [SettingsController::class, 'updateBanco'])->name('bancos.update');
        Route::delete('/bancos/{banco}', [SettingsController::class, 'destroyBanco'])->name('bancos.destroy');

        Route::get('/isapres/{isapre}/edit', [SettingsController::class, 'editIsapre'])->name('isapres.edit');
        Route::delete('/isapres/{isapre}', [SettingsController::class, 'destroyIsapre'])->name('isapres.destroy');

        //CCaf
        Route::get('/ccafs/{ccaf}/edit', [SettingsController::class, 'editCcaf'])->name('ccafs.edit');
        Route::delete('/ccafs/{ccaf}', [SettingsController::class, 'destroyCcaf'])->name('ccafs.destroy');

        //Mutuales
        Route::post('/mutuales', [SettingsController::class, 'storeMutual'])->name('mutuales.store');
        Route::put('/mutuales/{mutual}', [SettingsController::class, 'updateMutual'])->name('mutuales.update');
        Route::delete('/mutuales/{mutual}', [SettingsController::class, 'destroyMutual'])->name('mutuales.destroy');


        Route::put('/afps/{afp}', [SettingsController::class, 'updateAfp'])->name('afps.update');
        Route::put('/isapres/{isapre}', [SettingsController::class, 'updateIsapre'])->name('isapres.update');
        Route::put('/ccafs/{ccaf}', [SettingsController::class, 'updateCcaf'])->name('ccafs.update');
        
        
        Route::get('/legal-parameters', [SettingsController::class, 'legal'])->name('settings.legal');
        Route::put('/legal-parameters', [SettingsController::class, 'updateLegalParameters'])->name('legal_parameters.update');

        Route::get('/codigos-sii', [SettingsController::class, 'siiCodes'])->name('settings.sii_codes');

        // API para empresas
    
        Route::prefix('admin')
            ->middleware(['web', 'auth', 'role:super-admin|admin'])
            ->group(function () {
                Route::get('companies', [CompaniesApiController::class, 'index'])->name('admin.companies.index'); // ?search= & page=
            });

    });

Route::middleware(['auth', 'role:super-admin|admin'])->prefix('api/settings')->group(function () {
    Route::get('/ccafs', [SettingsController::class, 'ccafJson'])->name('settings.ccafs.json');
    Route::get('/mutuales', [SettingsController::class, 'mutualJson'])->name('settings.mutuales.json');
    Route::get('/isapres', [SettingsController::class, 'isapreJson'])->name('settings.isapres.json');
    Route::get('/afps', [SettingsController::class, 'afpJson'])->name('settings.afps.json');
    Route::get('/bancos', [SettingsController::class, 'bancoJson'])->name('settings.bancos.json');
});
