<?php

namespace App\Http\Middleware;

use App\Support\JsonApiError;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.allow_user_registration')) {
            return JsonApiError::response(
                '403',
                'Forbidden',
                'User registration is currently disabled.',
                null,
                null,
                403
            );
        }

        return $next($request);
    }
}
