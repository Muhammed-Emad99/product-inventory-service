<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a standardized success JSON response.
     */
    protected function successResponse(mixed $data = null, string $message = null, int $statusCode = 200, array $headers = []): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            if ($data instanceof LengthAwarePaginator) {
                $response['data'] = $data->items();
                $response['meta'] = [
                    'pagination' => [
                        'total' => $data->total(),
                        'count' => $data->count(),
                        'per_page' => $data->perPage(),
                        'current_page' => $data->currentPage(),
                        'total_pages' => $data->lastPage(),
                    ],
                ];
            } else {
                $response['data'] = $data;
            }
        }

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Return a standardized error JSON response.
     */
    protected function errorResponse(string $message, int $statusCode, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
