<?php

declare(strict_types=1);

namespace Modules\Core\Enums\Queue;

/**
 * Single Source of Truth (SSOT) for queue names.
 * Horizon config MUST reference this enum for the queue list; tuning (processes, balance, tries) stays in config/horizon.php.
 * New job classes MUST be registered in resolve() for explicit routing; unregistered jobs fall back to DEFAULT.
 */
enum QueueEnum: string
{
    case DEFAULT = 'default';

    /**
     * Resolve the queue for a job class. Unregistered jobs fall back to DEFAULT.
     * Register new job classes here explicitly to make routing obvious.
     */
    public static function resolve(string $jobClass): self
    {
        return match ($jobClass) {
            // Explicit job mappings: add job FQCN => self::NAMED_QUEUE when adding queues
            default => self::DEFAULT,
        };
    }
}
