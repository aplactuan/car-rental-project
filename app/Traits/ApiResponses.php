<?php

namespace App\Traits;

use App\Support\JsonApiError;
use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    protected function ok($message, $data = [], $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => ['message' => $message],
        ], $status);
    }

    protected function success($message, $data, $status): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => ['message' => $message],
        ], $status);
    }

    /**
     * JSON:API compliant error: { "errors": [ { "status", "title", "detail" } ] }
     */
    protected function error(string $detail, int $status, string $title = null): JsonResponse
    {
        $titles = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
        ];

        return JsonApiError::response(
            (string) $status,
            $title ?? $titles[$status] ?? 'Error',
            $detail,
            null,
            null,
            $status
        );
    }
}
