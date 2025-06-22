<?php

namespace App\Helpers\Api;

class ApiResponse
{


    // send response format
    public static function sendResponse($status = 200, $message = null, $data = null)
    {
        return response()->json([
            'status' => $status === 200,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
