<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminPanel\Http\Controllers\Auth\AuthenticatedSessionController;
use Modules\AdminPanel\Http\Controllers\AdminPanelController;
use Modules\AdminPanel\Http\Controllers\Auth\LogoutController;


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminPanelController::class, 'index'])->name('admin.dashboard');
    Route::post('logout', [LogoutController::class, 'destroy'])->name('logout');
});