<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * Route middleware aliases
         * Example usage in routes: ->middleware('role')
         */
        $middleware->alias([
            'auth'      => \Illuminate\Auth\Middleware\Authenticate::class,
            'guest'     => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'role'      => \App\Http\Middleware\RoleMiddleware::class, // your custom one
            'throttle'  => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified'  => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'device.license' => \App\Http\Middleware\CheckDeviceLicense::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Optional: add custom exception handling here
    })
    ->create();
