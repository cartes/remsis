<?php

use Illuminate\Support\Facades\Route;
use Modules\Requests\Http\Controllers\RequestsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('requests', RequestsController::class)->names('requests');
});
