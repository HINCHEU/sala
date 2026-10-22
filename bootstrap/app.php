<?php

use App\Http\Middleware\EnsureCurrentSchool;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Support\Tenancy\ResolveSchool;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

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

        // Tenant scopes must be on before route model binding, or {subject} could resolve another school's row.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: ResolveSchool::class);

        // Behind Nginx Proxy Manager TLS ends at the proxy. Trust X-Forwarded-* only from private
        // (Docker) addresses, so a client reaching a published port cannot fake its IP or scheme.
        $middleware->trustProxies(at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']);

        $middleware->alias([
            'two-factor.required' => EnsureTwoFactorEnabled::class,
            'school' => EnsureCurrentSchool::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
