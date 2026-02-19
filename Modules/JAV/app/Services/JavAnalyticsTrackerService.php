<?php

namespace Modules\JAV\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Services\AnalyticsIngestService;
use Modules\JAV\Models\Jav;

/**
 * Emits analytics events for server-side JAV movie actions.
 */
class JavAnalyticsTrackerService
{
    public function __construct(private readonly AnalyticsIngestService $ingestService) {}

    public function trackDownload(Jav $jav): void
    {
        $this->ingest($jav, AnalyticsAction::Download);
    }

    private function ingest(Jav $jav, AnalyticsAction $action): void
    {
        $this->ingestService->ingest([
            'event_id' => (string) Str::uuid(),
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => (string) $jav->uuid,
            'action' => $action->value,
            'value' => 1,
            'occurred_at' => Carbon::now()->toIso8601String(),
        ]);
    }
}
