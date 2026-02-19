<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Http\Requests\IngestAnalyticsEventRequest;
use Modules\Core\Services\AnalyticsIngestService;

/**
 * Public API endpoint for ingesting analytics events from FE/BE producers.
 */
class AnalyticsEventController
{
    public function __construct(private readonly AnalyticsIngestService $ingestService) {}

    public function store(IngestAnalyticsEventRequest $request): JsonResponse
    {
        /** @var array{event_id: string, domain: string, entity_type: string, entity_id: string, action: string, value?: int, occurred_at: string} $validated */
        $validated = $request->validated();
        $id = Auth::id();
        $userId = $id ? (int) $id : null;

        $this->ingestService->ingest($validated, $userId);

        return response()->json(['status' => 'accepted'], 202);
    }
}
