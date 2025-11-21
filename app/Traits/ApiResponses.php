<?php

namespace App\Traits;

trait ApiResponses
{
    protected function ok($message, $status = 200)
    {
        return $this->success($message, $status);
    }

    protected function success($message, $status)
    {
        return response()->json([
            'message' => $message,
            'status' => $status
        ], $status);
    }
}
