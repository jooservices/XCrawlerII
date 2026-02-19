<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if (class_exists(\Inertia\Middleware::class)) {
            $middleware->web(append: [
                \App\Http\Middleware\HandleInertiaRequests::class,
            ]);
        }

        if (($_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'testing') {
            $middleware->validateCsrfTokens(except: ['*']);
        }

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
