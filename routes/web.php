<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/robots.txt', function (): Response {
    $lines = [
        'User-agent: *',
        'Allow: /',
        'Disallow: /dashboard',
        'Disallow: /profile',
        'Disallow: /auth/login',
        'Sitemap: ' . url('/sitemap.xml'),
    ];

    return response(implode("\n", $lines) . "\n", 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
    ]);
});

Route::get('/sitemap.xml', function (): Response {
    $urls = [
        url('/'),
        url('/register'),
        url('/forgot-password'),
    ];

    $xml = view('sitemap', ['urls' => $urls])->render();

    return response($xml, 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
    ]);
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
