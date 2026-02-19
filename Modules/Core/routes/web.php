<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Api\V1\CurationController;
use Modules\Core\Http\Controllers\CoreController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cores', CoreController::class)->names('core');
});

Route::prefix('api/v1/curations')->name('api.curations.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [CurationController::class, 'index'])->name('index');

    Route::middleware(['role:admin'])->group(function () {
        Route::post('/', [CurationController::class, 'store'])->name('store');
        Route::delete('/{curation:uuid}', [CurationController::class, 'destroy'])->name('destroy');
    });
});
