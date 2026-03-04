<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Api\V1\ReactionController;

Route::prefix('v1/reactions')->name('v1.reactions.')->group(function () {
    Route::post('', [ReactionController::class, 'react'])->name('react');
});
