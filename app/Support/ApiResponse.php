<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        string $code,
        string $message,
        mixed $data,
        array $meta = [],
        int $status = 200,
    ): JsonResponse {
        $payload = [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function error(
        string $code,
        string $message,
        array $details = [],
        int $status = 422,
    ): JsonResponse {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ], $status);
    }
}
