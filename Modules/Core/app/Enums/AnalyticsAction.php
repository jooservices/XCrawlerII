<?php

namespace Modules\Core\Enums;

/**
 * Supported ingest actions and redis/mongo counter fields.
 */
enum AnalyticsAction: string
{
    case View = 'view';
    case Download = 'download';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $action): string => $action->value,
            self::cases()
        );
    }
}
