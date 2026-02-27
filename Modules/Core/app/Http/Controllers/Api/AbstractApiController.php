<?php

declare(strict_types=1);

namespace Modules\Core\app\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Routing\Controller;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiController extends Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    public const string MSG_UNAUTHENTICATED = 'Unauthenticated.';
    public const string MSG_FORBIDDEN = 'Forbidden.';
    public const string MSG_NOT_FOUND = 'Not found.';
    public const string MSG_CONFLICT = 'Conflict.';
    public const string MSG_INVALID = 'The given data was invalid.';
    public const string MSG_SERVER_ERROR = 'Server error.';

    protected function ok(mixed $data = null, array $meta = [], array $headers = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_OK, $meta, $headers);
    }

    protected function created(mixed $data = null, array $meta = [], array $headers = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_CREATED, $meta, $headers);
    }

    protected function accepted(mixed $data = null, array $meta = [], array $headers = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_ACCEPTED, $meta, $headers);
    }

    protected function noContent(array $headers = []): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT, $headers);
    }

    protected function success(mixed $data, int $status, array $meta = [], array $headers = []): JsonResponse
    {
        if ($data instanceof JsonResource) {
            if (!empty($meta)) {
                $data->additional(['meta' => $meta]);
            }

            return $data->response()->setStatusCode($status)->withHeaders($headers);
        }

        if ($data instanceof AbstractPaginator) {
            $paginationMeta = [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
            ];

            return response()->json([
                'data' => $data->items(),
                'meta' => array_merge($meta, ['pagination' => $paginationMeta]),
            ], $status, $headers);
        }

        return response()->json([
            'data' => $data,
            'meta' => empty($meta) ? new stdClass() : $meta,
        ], $status, $headers);
    }

    protected function error(string $message, int $status, array $errors = [], array $meta = [], array $headers = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => empty($errors) ? new stdClass() : $errors,
            'meta' => empty($meta) ? new stdClass() : $meta,
        ], $status, $headers);
    }

    protected function unauthorized(?string $message = null, array $meta = []): JsonResponse
    {
        return $this->error($message ?? self::MSG_UNAUTHENTICATED, Response::HTTP_UNAUTHORIZED, [], $meta);
    }

    protected function forbidden(?string $message = null, array $meta = []): JsonResponse
    {
        return $this->error($message ?? self::MSG_FORBIDDEN, Response::HTTP_FORBIDDEN, [], $meta);
    }

    protected function notFound(?string $message = null, array $meta = []): JsonResponse
    {
        return $this->error($message ?? self::MSG_NOT_FOUND, Response::HTTP_NOT_FOUND, [], $meta);
    }

    protected function conflict(?string $message = null, array $meta = []): JsonResponse
    {
        return $this->error($message ?? self::MSG_CONFLICT, Response::HTTP_CONFLICT, [], $meta);
    }

    protected function unprocessable(?string $message = null, array $errors = [], array $meta = []): JsonResponse
    {
        return $this->error($message ?? self::MSG_INVALID, Response::HTTP_UNPROCESSABLE_ENTITY, $errors, $meta);
    }

    protected function serverError(?string $message = null, array $meta = []): JsonResponse
    {
        return $this->error($message ?? self::MSG_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR, [], $meta);
    }
}
