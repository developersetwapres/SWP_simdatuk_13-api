<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

trait Responser
{
    public function response(int $code = 200, string $message = 'success', mixed $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public function paginateResponse(
        int $code = 200,
        string $message = 'success',
        mixed $data = null,
    ): JsonResponse {
        $pagination = [
            'total' => $data->total(),
            'count' => $data->lastItem(),
            'per_page' => (int) $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage(),
            'links' => [
                'first_page' => $data->url(1),
                'last_page' => $data->url($data->lastPage()),
                'next_page' => $data->nextPageUrl(),
                'prev_page' => $data->previousPageUrl(),
            ],
        ];

        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => $pagination,
        ], $code);
    }
}
