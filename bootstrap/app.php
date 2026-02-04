<?php

use App\Support\JsonApiError;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: [
            __DIR__.'/../routes/api.php',
            __DIR__.'/../routes/api/v1.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return JsonApiError::response(
                    '401',
                    'Unauthorized',
                    'You are not authorized to access this endpoint. Please log in.',
                    null,
                    null,
                    401
                );
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $errors = [];
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $detail) {
                        $errors[] = [
                            'status' => '422',
                            'title' => 'Validation Error',
                            'detail' => $detail,
                            'pointer' => '/data/attributes/'.str_replace('.', '/', $field),
                        ];
                    }
                }

                return JsonApiError::multiple($errors, 422);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return JsonApiError::response('404', 'Not Found', 'The requested resource was not found.', null, null, 404);
            }
        });
    })->create();
