<?php

use Illuminate\Support\Facades\Route;
use Modules\JAV\Http\Controllers\Admin\AnalyticsController;
use Modules\JAV\Http\Controllers\Admin\Api\AnalyticsController as AdminApiAnalyticsController;
use Modules\JAV\Http\Controllers\Admin\Api\JobTelemetryController as AdminApiJobTelemetryController;
use Modules\JAV\Http\Controllers\Admin\Api\SearchQualityController as AdminApiSearchQualityController;
use Modules\JAV\Http\Controllers\Admin\Api\SyncController as AdminApiSyncController;
use Modules\JAV\Http\Controllers\Admin\JobTelemetryController;
use Modules\JAV\Http\Controllers\Admin\SearchQualityController;
use Modules\JAV\Http\Controllers\Admin\SyncController;
use Modules\JAV\Http\Controllers\Guest\Auth\LoginController as GuestLoginController;
use Modules\JAV\Http\Controllers\Guest\Auth\RegisterController as GuestRegisterController;
use Modules\JAV\Http\Controllers\Users\Api\DashboardController as ApiDashboardController;
use Modules\JAV\Http\Controllers\Users\Api\LibraryController as ApiLibraryController;
use Modules\JAV\Http\Controllers\Users\Api\MovieController as ApiMovieController;
use Modules\JAV\Http\Controllers\Users\Api\NotificationController as ApiNotificationController;
use Modules\JAV\Http\Controllers\Users\Api\RatingController as ApiRatingController;
use Modules\JAV\Http\Controllers\Users\Api\SearchSuggestController as ApiSearchSuggestController;
use Modules\JAV\Http\Controllers\Users\Api\WatchlistController as ApiWatchlistController;
use Modules\JAV\Http\Controllers\Users\DashboardController;
use Modules\JAV\Http\Controllers\Users\JAVController;
use Modules\JAV\Http\Controllers\Users\MovieController;
use Modules\JAV\Http\Controllers\Users\NotificationController;
use Modules\JAV\Http\Controllers\Users\PreferenceController;
use Modules\JAV\Http\Controllers\Users\RatingController;
use Modules\JAV\Http\Controllers\Users\WatchlistController;

if (! defined('JAV_RATINGS_RATING_PATH')) {
    define('JAV_RATINGS_RATING_PATH', '/ratings/{rating}');
}
if (! defined('JAV_NOTIFICATIONS_PATH')) {
    define('JAV_NOTIFICATIONS_PATH', '/notifications');
}

Route::redirect('/jav', '/jav/dashboard');

Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/job-telemetry', [JobTelemetryController::class, 'indexVue'])->name('job-telemetry');
    Route::get('/job-telemetry/summary-data', [AdminApiJobTelemetryController::class, 'summary'])->name('job-telemetry.summary');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('javs', [JAVController::class, 'store'])->name('jav.store');
    Route::match(['PUT', 'PATCH'], 'javs/{jav}', [JAVController::class, 'update'])->name('jav.update');
    Route::delete('javs/{jav}', [JAVController::class, 'destroy'])->name('jav.destroy');
});

Route::middleware(['web',])->prefix('jav')->name('jav.vue.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'indexVue'])->name('dashboard');
    Route::get('/ui/movie-card-demo', [DashboardController::class, 'movieCardDemoVue'])->name('ui.movie-card-demo');
    Route::get('/ui/actor-tag-card-demo', [DashboardController::class, 'actorTagCardDemoVue'])->name('ui.actor-tag-card-demo');
    Route::get('/movies/{jav}', [JAVController::class, 'showVue'])->name('movies.show');
    Route::get('/actors', [DashboardController::class, 'actorsVue'])->name('actors');
    Route::get('/actors/{actor}/bio', [DashboardController::class, 'actorBioVue'])->name('actors.bio');
    Route::get('/tags', [DashboardController::class, 'tagsVue'])->name('tags');
    Route::get('/history', [DashboardController::class, 'historyVue'])->name('history');
    Route::get('/favorites', [DashboardController::class, 'favoritesVue'])->name('favorites');
    Route::get('/watchlist', [WatchlistController::class, 'indexVue'])->name('watchlist');
    Route::get('/recommendations', [DashboardController::class, 'recommendationsVue'])->name('recommendations');
    Route::get('/ratings', [RatingController::class, 'indexVue'])->name('ratings');
    Route::get(JAV_RATINGS_RATING_PATH, [RatingController::class, 'showVue'])->name('ratings.show');
    Route::get(JAV_NOTIFICATIONS_PATH, [DashboardController::class, 'notificationsVue'])->name('notifications');
    Route::get('/preferences', [DashboardController::class, 'preferencesVue'])->name('preferences');

    Route::prefix('javs')->name('javs.')->group(function () {
        Route::get('/', [JAVController::class, 'indexResourceVue'])->name('index');
        Route::get('/create', [JAVController::class, 'createResourceVue'])->name('create');
        Route::get('/{jav}', [JAVController::class, 'showResourceVue'])->name('show');
        Route::get('/{jav}/edit', [JAVController::class, 'editResourceVue'])->name('edit');
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/analytics', [AnalyticsController::class, 'indexVue'])->name('admin.analytics');
        Route::get('/admin/sync-progress', [SyncController::class, 'syncProgressVue'])->name('admin.sync-progress');
        Route::get('/admin/search-quality', [SearchQualityController::class, 'indexVue'])->name('admin.search-quality');
        Route::get('/admin/provider-sync', [SyncController::class, 'indexVue'])->name('admin.provider-sync');
    });
});

Route::middleware(['web', 'auth'])->prefix('jav/api')->name('jav.api.')->group(function () {
    Route::get('/dashboard/items', [ApiDashboardController::class, 'items'])->name('dashboard.items');
    Route::get('/search/suggest', [ApiSearchSuggestController::class, 'index'])->name('search.suggest');
    Route::post('/like', [ApiLibraryController::class, 'toggleLike'])->name('toggle-like');
    Route::post('/watchlist', [ApiWatchlistController::class, 'store'])->name('watchlist.store');
    Route::put('/watchlist/{watchlist}', [ApiWatchlistController::class, 'update'])->name('watchlist.update');
    Route::delete('/watchlist/{watchlist}', [ApiWatchlistController::class, 'destroy'])->name('watchlist.destroy');
    Route::get('/watchlist/check/{javId}', [ApiWatchlistController::class, 'check'])->name('watchlist.check');
    Route::post('/ratings', [ApiRatingController::class, 'store'])->name('ratings.store');
    Route::put(JAV_RATINGS_RATING_PATH, [ApiRatingController::class, 'update'])->name('ratings.update');
    Route::delete(JAV_RATINGS_RATING_PATH, [ApiRatingController::class, 'destroy'])->name('ratings.destroy');
    Route::get('/ratings/check/{javId}', [ApiRatingController::class, 'check'])->name('ratings.check');
    Route::get(JAV_NOTIFICATIONS_PATH, [ApiNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [ApiNotificationController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [ApiNotificationController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
});

Route::prefix('jav')->name('jav.')->group(function () {
    Route::get('/movies/{jav}/download', [MovieController::class, 'download'])->name('movies.download');
    Route::post('/movies/{jav}/view', [ApiMovieController::class, 'view'])->name('movies.view');

    Route::middleware('auth')->group(function () {
        // Temporary legacy alias: keep route('jav.notifications') resolving to canonical Vue page.
        Route::get(JAV_NOTIFICATIONS_PATH, [DashboardController::class, 'notificationsVue'])->name('notifications');
        Route::post('/like', [ApiLibraryController::class, 'toggleLike'])->name('toggle-like');
        Route::post('/preferences', [PreferenceController::class, 'save'])->name('preferences.save');
        Route::post('/account/avatar', [PreferenceController::class, 'updateAvatar'])->name('account.avatar.update');
        Route::delete('/account/avatar', [PreferenceController::class, 'removeAvatar'])->name('account.avatar.remove');
        Route::put('/account/profile', [PreferenceController::class, 'updateProfile'])->name('account.profile.update');
        Route::put('/account/password', [PreferenceController::class, 'updatePassword'])->name('account.password.update');
        Route::post('/presets', [PreferenceController::class, 'savePreset'])->name('presets.save');
        Route::delete('/presets/{presetKey}', [PreferenceController::class, 'deletePreset'])->name('presets.delete');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markNotificationRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
    });

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/admin/analytics/distribution-data', [AdminApiAnalyticsController::class, 'distribution'])->name('admin.analytics.distribution');
        Route::get('/admin/analytics/suggest', [AdminApiAnalyticsController::class, 'suggest'])->name('admin.analytics.suggest');
        Route::get('/admin/analytics/association-data', [AdminApiAnalyticsController::class, 'association'])->name('admin.analytics.association');
        Route::get('/admin/analytics/trends-data', [AdminApiAnalyticsController::class, 'trends'])->name('admin.analytics.trends');
        Route::get('/admin/analytics/overview-data', [AdminApiAnalyticsController::class, 'overview'])->name('admin.analytics.overview');
        Route::post('/admin/analytics/predict', [AdminApiAnalyticsController::class, 'predict'])->name('admin.analytics.predict');
        Route::get('/admin/analytics/actor-insights', [AdminApiAnalyticsController::class, 'actorInsights'])->name('admin.analytics.actor-insights');
        Route::get('/admin/analytics/quality-data', [AdminApiAnalyticsController::class, 'quality'])->name('admin.analytics.quality');
        Route::get('/admin/provider-sync/status', [AdminApiSyncController::class, 'providerSyncStatus'])->name('admin.provider-sync.status');
        Route::get('/admin/sync-progress/data', [AdminApiSyncController::class, 'syncProgressData'])->name('admin.sync-progress.data');
        Route::post('/admin/search-quality/preview', [AdminApiSearchQualityController::class, 'preview'])->name('admin.search-quality.preview');
        Route::post('/admin/search-quality/publish', [AdminApiSearchQualityController::class, 'publish'])->name('admin.search-quality.publish');
        Route::post('/admin/provider-sync/dispatch', [AdminApiSyncController::class, 'dispatch'])->name('admin.provider-sync.dispatch');
    });
});

// Watchlist action routes
Route::middleware('auth')->prefix('watchlist')->name('watchlist.')->group(function () {
    Route::post('/', [WatchlistController::class, 'store'])->name('store');
    Route::put('/{watchlist}', [WatchlistController::class, 'update'])->name('update');
    Route::delete('/{watchlist}', [WatchlistController::class, 'destroy'])->name('destroy');
    Route::get('/check/{javId}', [WatchlistController::class, 'check'])->name('check');
});

// Rating action routes
Route::prefix('ratings')->name('ratings.')->group(function () {
    Route::get('/check/{javId}', [RatingController::class, 'check'])->name('check');

    Route::middleware('auth')->group(function () {
        Route::post('/', [RatingController::class, 'store'])->name('store');
        Route::put('/{rating}', [RatingController::class, 'update'])->name('update');
        Route::delete('/{rating}', [RatingController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware('guest')->group(function () {
    Route::get('/jav/login', [GuestLoginController::class, 'showLoginFormVue'])->name('jav.vue.login');
    Route::get('/jav/register', [GuestRegisterController::class, 'showRegistrationFormVue'])->name('jav.vue.register');
    Route::get('/login', [GuestLoginController::class, 'showLoginFormVue'])->name('login');
    Route::post('/login', [GuestLoginController::class, 'login']);
    Route::get('/register', [GuestRegisterController::class, 'showRegistrationFormVue'])->name('register');
    Route::post('/register', [GuestRegisterController::class, 'register']);
});

Route::post('/logout', [GuestLoginController::class, 'logout'])->middleware('auth')->name('logout');
