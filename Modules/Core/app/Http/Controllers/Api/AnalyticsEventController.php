<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Requests\IngestAnalyticsEventRequest;
use Modules\Core\Services\AnalyticsIngestService;

class AnalyticsEventController
{
    public function __construct(private readonly AnalyticsIngestService $ingestService) {}

    public function store(IngestAnalyticsEventRequest $request): JsonResponse
    {
        if (! (bool) config('analytics.enabled', false)) {
            return response()->json(['status' => 'accepted'], 202);
        }

        /** @var array{domain: string, entity_type: string, entity_id: string, action: string, value?: int, occurred_at: string} $validated */
        $validated = $request->validated();
        $userId = auth()->id();

        $this->ingestService->ingest($validated, $userId);

        return response()->json(['status' => 'accepted'], 202);
    }
}
