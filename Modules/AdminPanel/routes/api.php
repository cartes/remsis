<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminPanel\Http\Controllers\AdminPanelController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('adminpanels', AdminPanelController::class)->names('adminpanel');
});
