<?php

namespace Modules\JAV\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

abstract class ApiController extends Controller
{
    protected function result(array $payload = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge(['success' => true], $payload), $status);
    }

    protected function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function error(
        string $message = 'Request failed',
        int $status = 400,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }
        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function created(
        mixed $data = null,
        string $message = 'Created',
        array $meta = []
    ): JsonResponse {
        return $this->success($data, $message, 201, $meta);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function paginated(
        LengthAwarePaginator|Paginator $paginator,
        string $message = 'OK'
    ): JsonResponse {
        return $this->success(
            $paginator->items(),
            $message,
            200,
            [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => method_exists($paginator, 'total') ? $paginator->total() : null,
                    'last_page' => method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null,
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                ],
            ]
        );
    }

    protected function collection(
        Collection|array $items,
        string $message = 'OK',
        array $meta = []
    ): JsonResponse {
        return $this->success(
            $items instanceof Collection ? $items->values()->all() : $items,
            $message,
            200,
            $meta
        );
    }
}
