<?php

use App\Http\Middleware\EnsureCurrentSchool;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Support\Tenancy\ResolveSchool;
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
        // Order matters: the school decides the default locale, and Inertia shares both.
        $middleware->web(append: [
            ResolveSchool::class,
            SetLocale::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'two-factor.required' => EnsureTwoFactorEnabled::class,
            'school' => EnsureCurrentSchool::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
