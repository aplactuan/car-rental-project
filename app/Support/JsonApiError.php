<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Build JSON:API 1.1 compliant error responses.
 * @see https://jsonapi.org/format/#error-objects
 */
final class JsonApiError
{
    /**
     * Return a JSON response containing a single error object.
     */
    public static function response(
        string $status,
        string $title,
        string $detail,
        ?string $pointer = null,
        ?string $parameter = null,
        int $httpStatus = null
    ): JsonResponse {
        $httpStatus = $httpStatus ?? (int) $status;
        $error = [
            'status' => $status,
            'title' => $title,
            'detail' => $detail,
        ];
        if ($pointer !== null) {
            $error['source'] = ['pointer' => $pointer];
        }
        if ($parameter !== null) {
            $error['source'] = array_merge($error['source'] ?? [], ['parameter' => $parameter]);
        }

        return response()->json(['errors' => [$error]], $httpStatus);
    }

    /**
     * Return a JSON response containing multiple error objects (e.g. validation).
     *
     * @param  array<int, array{status?: string, title: string, detail: string, pointer?: string, parameter?: string}>  $errors
     */
    public static function multiple(array $errors, int $httpStatus = 422): JsonResponse
    {
        $payload = array_map(function (array $e) {
            $obj = [
                'status' => $e['status'] ?? '422',
                'title' => $e['title'],
                'detail' => $e['detail'],
            ];
            if (! empty($e['pointer'])) {
                $obj['source'] = ['pointer' => $e['pointer']];
            }
            if (! empty($e['parameter'])) {
                $obj['source'] = array_merge($obj['source'] ?? [], ['parameter' => $e['parameter']]);
            }
            return $obj;
        }, $errors);

        return response()->json(['errors' => $payload], $httpStatus);
    }
}
