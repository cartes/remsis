<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminPanel\Http\Controllers\Auth\AuthenticatedSessionController;
use Modules\AdminPanel\Http\Controllers\AdminPanelController;
use Modules\AdminPanel\Http\Controllers\Auth\LogoutController;
use Modules\AdminPanel\Http\Controllers\SettingsController;


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminPanelController::class, 'index'])->name('admin.dashboard');
    Route::post('logout', [LogoutController::class, 'destroy'])->name('logout');
});

Route::prefix('settings')
    ->middleware(['web', 'auth', 'role:super-admin'])
    ->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/afps', [SettingsController::class, 'storeAfp'])->name('afps.store');
        Route::post('/isapres', [SettingsController::class, 'storeIsapre'])->name('isapres.store');
        Route::post('/ccafs', [SettingsController::class, 'storeCcaf'])->name('ccafs.store');

        Route::get('/afps/{afp}/edit', [SettingsController::class, 'editAfp'])->name('afps.edit');
        Route::delete('/afps/{afp}', [SettingsController::class, 'destroyAfp'])->name('afps.destroy');

        Route::get('/isapres/{isapre}/edit', [SettingsController::class, 'editIsapre'])->name('isapres.edit');
        Route::delete('/isapres/{isapre}', [SettingsController::class, 'destroyIsapre'])->name('isapres.destroy');

        //CCaf
        Route::get('/ccafs/{ccaf}/edit', [SettingsController::class, 'editCcaf'])->name('ccafs.edit');
        Route::delete('/ccafs/{ccaf}', [SettingsController::class, 'destroyCcaf'])->name('ccafs.destroy');

        Route::put('/afps/{afp}', [SettingsController::class, 'updateAfp'])->name('afps.update');
        Route::put('/isapres/{isapre}', [SettingsController::class, 'updateIsapre'])->name('isapres.update');
        Route::put('/ccafs/{ccaf}', [SettingsController::class, 'updateCcaf'])->name('ccafs.update');

    });
