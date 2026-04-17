<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ApiJson
{
    /**
     * @param  array<string, list<string>>|null  $errors
     */
    public static function error(
        string $message,
        int $status = 500,
        ?string $code = null,
        ?array $errors = null,
    ): JsonResponse {
        $payload = ['message' => $message];
        if ($code !== null && $code !== '') {
            $payload['code'] = $code;
        }
        if ($errors !== null && $errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
