<?php

namespace App\Traits;

trait HttpResponses {
    protected function apiResponse($message = null, $status_code = 200, $data = null)
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ], $status_code);
    }
}
