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
        // cloudflared forwards HTTPS requests from the loopback interface.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
        $middleware->validateCsrfTokens(except: ['webhook/casaku']);
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'admin.2fa' => \App\Http\Middleware\EnsureAdminTwoFactor::class,
            'not.blocked' => \App\Http\Middleware\EnsureNotBlocked::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
