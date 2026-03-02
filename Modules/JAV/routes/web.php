<?php

use Illuminate\Support\Facades\Route;
use Modules\JAV\Http\Controllers\JAVController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('javs', JAVController::class)->names('jav');
});
