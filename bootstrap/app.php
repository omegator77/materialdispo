<?php

use App\Http\Middleware\CheckRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => CheckRole::class,
        ]);

        // Nur Reverse-Proxys vertrauen, nicht jedem Absender: Mit '*' könnte jeder
        // Client per X-Forwarded-For eine beliebige IP vortäuschen und damit das
        // Login-Rate-Limit umgehen. Die Proxy-Liste steht in config/trustedproxy.php
        // (TRUSTED_PROXIES, Standard: private Netze). Sie gehört bewusst in eine
        // Config-Datei: env() außerhalb von config/ liefert bei config:cache NULL.
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->validateCsrfTokens(except: [
            'slack/interactions',
            'slack/commands',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
