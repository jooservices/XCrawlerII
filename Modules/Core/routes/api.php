<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Api\AnalyticsEventController;
use Modules\Core\Http\Controllers\CoreController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cores', CoreController::class)->names('core');
});

Route::prefix('v1/analytics')->middleware('throttle:analytics')->group(function () {
    Route::post('/events', [AnalyticsEventController::class, 'store'])
        ->name('analytics.events.store');
});
