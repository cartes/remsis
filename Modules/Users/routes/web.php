<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\RoleController;
use Modules\Users\Http\Controllers\UserController;

Route::middleware(['web', 'auth'])->prefix("users")->group(function () {
    Route::get("/", [UserController::class, "index"])->name("users.index");
    Route::post("/", [UserController::class, "store"])->name("users.store");
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
    Route::get('/{user}/json', [UserController::class, 'showJson'])->name('users.json');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get("/roles", [RoleController::class, "index"])->name("roles.index");

    Route::put('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
});