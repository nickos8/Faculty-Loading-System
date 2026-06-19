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
    ->withMiddleware(callback: function (Middleware $middleware): void {
        /**
         * Register short names ("aliases") for route middleware.
         * After this, you can use ->middleware('active') or ->middleware('role:super_admin') on routes.
         *
         * Important (Laravel 12): middleware aliases live here in bootstrap/app.php,
         * not in the old app/Http/Kernel.php.
         */
        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'role'   => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })

    // bootstrap/app.php
->withMiddleware(function (Illuminate\Foundation\Configuration\Middleware $middleware) {
    $middleware->alias([
        'role'   => \App\Http\Middleware\RoleMiddleware::class,
        // 'active' => \App\Http\Middleware\EnsureUserIsActive::class, // optional
    ]);
})

    ->withExceptions(using: function (Exceptions $exceptions): void {
        // Keep your exception customization here if/when you add some.
    })
    ->create();
