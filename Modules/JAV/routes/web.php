<?php

use Illuminate\Support\Facades\Route;
use Modules\JAV\Http\Controllers\DashboardController;
use Modules\JAV\Http\Controllers\JAVController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('javs', JAVController::class)->names('jav');
});

Route::controller(DashboardController::class)->prefix('jav')->name('jav.')->group(function () {
    Route::get('/dashboard', 'index')->name('dashboard');
    Route::get('/movies/{jav}', 'show')->name('movies.show');
    Route::get('/movies/{jav}/download', 'download')->name('movies.download');
    Route::post('/movies/{jav}/view', 'view')->name('movies.view');
    Route::post('/request', 'request')->name('request');
    Route::get('/status', 'status')->name('status');
    Route::get('/actors', 'actors')->name('actors');
    Route::get('/tags', 'tags')->name('tags');

    Route::middleware('auth')->group(function () {
        Route::post('/like', 'toggleLike')->name('toggle-like');
        Route::get('/history', 'history')->name('history');
        Route::get('/favorites', 'favorites')->name('favorites');
        Route::get('/recommendations', 'recommendations')->name('recommendations');
    });
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [\Modules\JAV\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\Modules\JAV\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::get('/register', [\Modules\JAV\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [\Modules\JAV\Http\Controllers\Auth\RegisterController::class, 'register']);
});

Route::post('/logout', [\Modules\JAV\Http\Controllers\Auth\LoginController::class, 'logout'])->middleware('auth')->name('logout');
