<?php

namespace Modules\Core\Services;

use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\JAV\Models\Jav;

/**
 * Compares MySQL replica counters and Mongo totals for parity checks.
 */
class AnalyticsParityService
{
    /**
     * @return array{checked:int,mismatches:int,rows:array<int,array<string,int|string>>}
     */
    public function check(int $limit = 100): array
    {
        $rows = [];
        $checked = 0;

        $movies = Jav::query()
            ->orderByDesc('views')
            ->limit($limit)
            ->get(['uuid', 'code', 'views', 'downloads']);

        foreach ($movies as $movie) {
            $mongo = AnalyticsEntityTotals::query()
                ->where('domain', AnalyticsDomain::Jav->value)
                ->where('entity_type', AnalyticsEntityType::Movie->value)
                ->where('entity_id', (string) $movie->uuid)
                ->first();

            $mongoViews = (int) data_get($mongo, AnalyticsAction::View->value, 0);
            $mongoDownloads = (int) data_get($mongo, AnalyticsAction::Download->value, 0);
            $mysqlViews = (int) ($movie->views ?? 0);
            $mysqlDownloads = (int) ($movie->downloads ?? 0);

            if ($mongoViews !== $mysqlViews || $mongoDownloads !== $mysqlDownloads) {
                $rows[] = [
                    'code' => (string) ($movie->code ?? $movie->uuid),
                    'mysql_views' => $mysqlViews,
                    'mysql_downloads' => $mysqlDownloads,
                    'mongo_views' => $mongoViews,
                    'mongo_downloads' => $mongoDownloads,
                ];
            }

            $checked++;
        }

        return [
            'checked' => $checked,
            'mismatches' => count($rows),
            'rows' => $rows,
        ];
    }
}
