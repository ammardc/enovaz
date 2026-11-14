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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'reauth' => \App\Http\Middleware\RequireRecentPassword::class,
            '2fa' => \App\Http\Middleware\RequireTwoFactor::class,
        ]);

        // Webhook Casaku diverifikasi dengan HMAC, bukan token CSRF.
        $middleware->validateCsrfTokens(except: ['webhooks/casaku']);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));

        // Header keamanan untuk SEMUA respons (termasuk 404/405 yang terjadi sebelum grup web).
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->web(append: [
            // Sesi yang hash password-nya sudah usang (password diganti) otomatis dikeluarkan.
            \Illuminate\Session\Middleware\AuthenticateSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
