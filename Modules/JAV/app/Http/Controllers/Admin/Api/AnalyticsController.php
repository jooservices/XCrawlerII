<?php

namespace Modules\JAV\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\JAV\Exceptions\ElasticsearchUnavailableException;
use Modules\JAV\Http\Requests\AnalyticsApiRequest;
use Modules\JAV\Services\ActorAnalyticsService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AnalyticsController extends Controller
{
    public function __construct(private readonly ActorAnalyticsService $actorAnalyticsService) {}

    public function distribution(AnalyticsApiRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $dimension = (string) ($payload['dimension'] ?? 'age_bucket');
        $genre = trim((string) ($payload['genre'] ?? ''));
        $size = (int) ($payload['size'] ?? 10);

        if ($genre === '') {
            return response()->json(['message' => 'Genre is required.'], 422);
        }

        return $this->execute(fn (): array => $this->actorAnalyticsService->distribution($dimension, $genre, $size));
    }

    public function association(AnalyticsApiRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $segmentType = (string) ($payload['segment_type'] ?? 'age_bucket');
        $segmentValue = trim((string) ($payload['segment_value'] ?? ''));
        $size = (int) ($payload['size'] ?? 10);
        $minSupport = (int) ($payload['min_support'] ?? 1);

        if ($segmentValue === '') {
            return response()->json(['message' => 'Segment value is required.'], 422);
        }

        return $this->execute(fn (): array => $this->actorAnalyticsService->association($segmentType, $segmentValue, $size, $minSupport));
    }

    public function trends(AnalyticsApiRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $dimension = (string) ($payload['dimension'] ?? 'age_bucket');
        $genre = isset($payload['genre']) ? trim((string) $payload['genre']) : null;
        $interval = (string) ($payload['interval'] ?? 'month');
        $size = (int) ($payload['size'] ?? 5);

        return $this->execute(fn (): array => $this->actorAnalyticsService->trends($dimension, $genre, $interval, $size));
    }

    public function predict(AnalyticsApiRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $size = (int) ($payload['size'] ?? 5);

        return $this->execute(fn (): array => $this->actorAnalyticsService->predictGenres($payload, $size));
    }

    public function quality(): JsonResponse
    {
        return $this->execute(fn (): array => $this->actorAnalyticsService->quality());
    }

    public function overview(AnalyticsApiRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $size = (int) ($payload['size'] ?? 8);

        return $this->execute(fn (): array => $this->actorAnalyticsService->overview($size));
    }

    public function suggest(AnalyticsApiRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $type = (string) ($payload['type'] ?? 'genre');
        $query = trim((string) ($payload['q'] ?? ''));
        $size = (int) ($payload['size'] ?? 8);

        return $this->execute(fn (): array => $this->actorAnalyticsService->suggestions($type, $query, $size));
    }

    public function actorInsights(AnalyticsApiRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $actorUuid = trim((string) ($payload['actor_uuid'] ?? ''));
        $size = (int) ($payload['size'] ?? 5);

        if ($actorUuid === '') {
            return response()->json(['message' => 'Actor UUID is required.'], 422);
        }

        return $this->execute(function () use ($actorUuid, $size): array {
            $result = $this->actorAnalyticsService->actorInsights($actorUuid, $size);

            if ($result === null) {
                abort(404, 'Actor not found.');
            }

            return $result;
        });
    }

    /**
     * @param  callable(): array<string, mixed>  $callback
     */
    private function execute(callable $callback): JsonResponse
    {
        try {
            return response()->json($callback());
        } catch (\Throwable $exception) {
            return $this->exceptionResponse($exception);
        }
    }

    private function exceptionResponse(\Throwable $exception): JsonResponse
    {
        $status = 500;
        $message = 'Analytics request failed.';

        if ($exception instanceof HttpException) {
            $status = $exception->getStatusCode();
            $message = $exception->getMessage() !== '' ? $exception->getMessage() : 'Request failed.';
        } elseif ($exception instanceof ElasticsearchUnavailableException) {
            $status = 503;
            $message = $exception->getMessage();
        } else {
            report($exception);
        }

        return response()->json([
            'message' => $message,
        ], $status);
    }
}
