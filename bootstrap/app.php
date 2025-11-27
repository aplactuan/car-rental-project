<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        #api: __DIR__.'/../routes/api.php',
        api: [
            __DIR__.'/../routes/api.php',
            __DIR__.'/../routes/api/v1.php',
            __DIR__.'/../routes/api/v2.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
       #$middleware->append()
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {

            }
            return response()->json([
                'message' => 'You are not authorized to access this endpoint. Please log in.',
            ], 401);
        });
    })->create();
