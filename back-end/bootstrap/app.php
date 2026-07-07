<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // FORCE GLOBAL CORS FIRST
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // Note: this API uses pure Sanctum bearer-token auth (Authorization header),
        // not Sanctum's cookie-based SPA flow, so EnsureFrontendRequestsAreStateful is
        // intentionally not registered here — it would force session/CSRF handling
        // onto stateless token requests and break POSTs with a 419.

        $middleware->web(append: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
