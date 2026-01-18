<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    /**
     * Return a success response.
     */
    protected function successResponse(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge(['success' => true], $data), $status);
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(string $message, int $status = 400, ?string $code = null): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => $message,
        ];

        if ($code !== null) {
            $response['code'] = $code;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a validation error response.
     */
    protected function validationErrorResponse(string $message, int $status = 422): JsonResponse
    {
        return $this->errorResponse($message, $status, 'VALIDATION_ERROR');
    }

    /**
     * Return a not found error response.
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404, 'NOT_FOUND');
    }

    /**
     * Return a forbidden error response.
     */
    protected function forbiddenResponse(string $message = 'Access denied'): JsonResponse
    {
        return $this->errorResponse($message, 403, 'FORBIDDEN');
    }

    /**
     * Return a server error response.
     */
    protected function serverErrorResponse(string $message = 'An unexpected error occurred'): JsonResponse
    {
        return $this->errorResponse($message, 500, 'SERVER_ERROR');
    }
}
