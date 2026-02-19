<?php

namespace Modules\JAV\Http\Controllers\Users\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Services\AnalyticsIngestService;
use Modules\JAV\Http\Controllers\Api\ApiController;
use Modules\JAV\Models\Jav;

class MovieController extends ApiController
{
    public function view(Jav $jav): JsonResponse
    {
        if (! (bool) config('analytics.enabled', false)) {
            $jav->increment('views');
        } else {
            app(AnalyticsIngestService::class)->ingest([
                'event_id' => (string) Str::uuid(),
                'domain' => 'jav',
                'entity_type' => 'movie',
                'entity_id' => (string) $jav->uuid,
                'action' => AnalyticsAction::View->value,
                'value' => 1,
                'occurred_at' => now('UTC')->format('Y-m-d\\TH:i:s\\Z'),
            ]);
        }

        return response()->json([
            'views' => (int) $jav->fresh()->views,
        ]);
    }
}
