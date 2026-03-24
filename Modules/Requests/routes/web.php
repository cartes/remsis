<?php

use Illuminate\Support\Facades\Route;
use Modules\Requests\Http\Controllers\RequestsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('requests', RequestsController::class)->names('requests');
});
