<?php

use Illuminate\Support\Facades\Route;
use Modules\JAV\Http\Controllers\DashboardController;
use Modules\JAV\Http\Controllers\JAVController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('javs', JAVController::class)->names('jav');
});

Route::controller(DashboardController::class)->prefix('jav/dashboard')->name('jav.dashboard')->group(function () {
    Route::get('/', 'index');
    Route::get('/actors', 'actors')->name('.actors');
    Route::get('/tags', 'tags')->name('.tags');
    Route::get('/download/{jav}', 'download')->name('.download');
    Route::post('/request', 'request')->name('.request');
    Route::get('/status', 'status')->name('.status');
});
