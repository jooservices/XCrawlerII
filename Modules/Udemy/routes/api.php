<?php

use Illuminate\Support\Facades\Route;
use Modules\Udemy\Http\Controllers\UdemyController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::prefix('v1/udemy')
    ->name('udemy.')
    ->group(function () {
        Route::post('/users', [UdemyController::class, 'create'])->name('create');
    });
