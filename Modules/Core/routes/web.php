<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;

Route::middleware(['auth'])->group(function () {
    Route::resource('cores', CoreController::class)->names('core');
});
