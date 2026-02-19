<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;

class AnalyticsParityService
{
    /**
     * @return array{checked:int,mismatches:int,rows:array<int,array<string,int|string>>}
     */
    public function check(int $limit = 100): array
    {
        $rows = [];
        $checked = 0;

        $movies = DB::table('jav')
            ->orderByDesc('views')
            ->limit($limit)
            ->get(['uuid', 'code', 'views', 'downloads']);

        foreach ($movies as $movie) {
            $mongo = DB::connection('mongodb')
                ->collection('analytics_entity_totals')
                ->where('domain', 'jav')
                ->where('entity_type', 'movie')
                ->where('entity_id', (string) $movie->uuid)
                ->first();

            $mongoViews = (int) data_get($mongo, 'view', 0);
            $mongoDownloads = (int) data_get($mongo, 'download', 0);
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
