<?php

namespace Modules\Core\Enums;

/**
 * Canonical analytics domains accepted by ingest and rollup pipelines.
 */
enum AnalyticsDomain: string
{
    case Jav = 'jav';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $domain): string => $domain->value,
            self::cases()
        );
    }
}
