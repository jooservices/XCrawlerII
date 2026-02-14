<?php

use Illuminate\Support\Facades\Route;
use Modules\JAV\Http\Controllers\Admin\ProviderSyncController;
use Modules\JAV\Http\Controllers\Admin\SearchQualityController;
use Modules\JAV\Http\Controllers\DashboardController;
use Modules\JAV\Http\Controllers\JAVController;
use Modules\JAV\Http\Controllers\RatingController;
use Modules\JAV\Http\Controllers\WatchlistController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('javs', JAVController::class)->names('jav');
});

Route::controller(DashboardController::class)->prefix('jav')->name('jav.')->group(function () {
    Route::get('/dashboard', 'index')->name('dashboard');
    Route::get('/movies/{jav}', 'show')->name('movies.show');
    Route::get('/movies/{jav}/download', 'download')->name('movies.download');
    Route::post('/movies/{jav}/view', 'view')->name('movies.view');
    Route::get('/actors', 'actors')->name('actors');
    Route::get('/actors/{actor}/bio', 'actorBio')->name('actors.bio');
    Route::get('/tags', 'tags')->name('tags');

    Route::middleware('auth')->group(function () {
        Route::post('/like', 'toggleLike')->name('toggle-like');
        Route::get('/history', 'history')->name('history');
        Route::get('/favorites', 'favorites')->name('favorites');
        Route::get('/recommendations', 'recommendations')->name('recommendations');
        Route::get('/notifications', 'notifications')->name('notifications');
        Route::post('/notifications/{notification}/read', 'markNotificationRead')->name('notifications.read');
        Route::post('/notifications/read-all', 'markAllNotificationsRead')->name('notifications.read-all');
    });

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::post('/request', 'request')->name('request');
        Route::get('/status', 'status')->name('status');
        Route::get('/admin/sync-progress', 'syncProgress')->name('admin.sync-progress');
        Route::get('/admin/sync-progress/data', 'syncProgressData')->name('admin.sync-progress.data');

        Route::controller(SearchQualityController::class)->prefix('/admin/search-quality')->name('admin.search-quality.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/preview', 'preview')->name('preview');
            Route::post('/publish', 'publish')->name('publish');
        });

        Route::controller(ProviderSyncController::class)->prefix('/admin/provider-sync')->name('admin.provider-sync.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/dispatch', 'dispatch')->name('dispatch');
        });
    });
});

// Watchlist Routes
Route::middleware('auth')->prefix('watchlist')->name('watchlist.')->controller(WatchlistController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::put('/{watchlist}', 'update')->name('update');
    Route::delete('/{watchlist}', 'destroy')->name('destroy');
    Route::get('/check/{javId}', 'check')->name('check');
});

// Rating Routes
Route::prefix('ratings')->name('ratings.')->controller(RatingController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{rating}', 'show')->name('show');
    Route::get('/check/{javId}', 'check')->name('check');

    Route::middleware('auth')->group(function () {
        Route::post('/', 'store')->name('store');
        Route::put('/{rating}', 'update')->name('update');
        Route::delete('/{rating}', 'destroy')->name('destroy');
    });
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [\Modules\JAV\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\Modules\JAV\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::get('/register', [\Modules\JAV\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [\Modules\JAV\Http\Controllers\Auth\RegisterController::class, 'register']);
});

Route::post('/logout', [\Modules\JAV\Http\Controllers\Auth\LoginController::class, 'logout'])->middleware('auth')->name('logout');
