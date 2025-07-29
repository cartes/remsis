<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\RoleController;
use Modules\Users\Http\Controllers\UserController;

Route::middleware(['web', 'auth'])->prefix("users")->group(function() {
    Route::get("/usuarios", [UserController::class,"index"])->name("users.index");
    Route::get("/roles", [RoleController::class,"index"])->name("roles.index");
});