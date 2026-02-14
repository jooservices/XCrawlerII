<?php

use Illuminate\Support\Facades\Route;
use Modules\JAV\Http\Controllers\Users\JAVController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('javs', JAVController::class)->names('jav');
});
