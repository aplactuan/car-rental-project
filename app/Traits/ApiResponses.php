<?php

namespace App\Traits;

trait ApiResponses
{
    protected function ok($message, $data=[], $status = 200)
    {
        return $this->success($message, $data, $status);
    }

    protected function success($message, $data, $status)
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'status' => $status
        ], $status);
    }

    protected function error($message, $status)
    {
        return response()->json([
            'message' => $message,
            'status' => $status
        ], $status);
    }
}
