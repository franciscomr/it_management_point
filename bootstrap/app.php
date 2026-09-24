<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\Shared\Exceptions\ApiException;
use App\Modules\Shared\Middleware\EnsureAuthenticatedUserBelongsToTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->group('api', [
            \App\Modules\Shared\Http\Middleware\TenantMiddleware::class,
        ]);

        $middleware->alias(['tenant.match' => EnsureAuthenticatedUserBelongsToTenant::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (!$request->expectsJson()) {
                return null; // deja que Laravel maneje la respuesta por su cuenta
            }

            if ($e instanceof ApiException) {
                return response()->json($e->toArray(), $e->status());
            }

            if (app()->hasDebugModeEnabled()) {
                return null; // en local, deja ver el stack trace real de Laravel
            }

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'code' => 'INTERNAL_ERROR',
                'errors' => [],
                'meta' => [],
            ], 500);
        });
    })->create();
