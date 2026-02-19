<?php

namespace Modules\Core\Enums;

/**
 * Supported entity types for analytics events and rollups.
 */
enum AnalyticsEntityType: string
{
    case Movie = 'movie';
    case Actor = 'actor';
    case Tag = 'tag';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $entityType): string => $entityType->value,
            self::cases()
        );
    }
}
