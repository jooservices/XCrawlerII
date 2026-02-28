<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Auth\LoginController;
use Modules\Core\Http\Controllers\CoreController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cores', CoreController::class)->names('core');
});

Route::prefix('auth')->name('v1.render.auth.')->middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'renderLogin'])->name('login');
});

Route::prefix('auth')->name('v1.action.auth.')->group(function () {
    Route::post('/login', [LoginController::class, 'actionLogin'])->middleware('guest')->name('login');
    Route::post('/logout', [LoginController::class, 'actionLogout'])->middleware('auth')->name('logout');
});
