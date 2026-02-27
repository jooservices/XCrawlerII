<?php

declare(strict_types=1);

namespace Modules\Core\Support\Logging;

use Modules\Core\Models\Log;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

/**
 * Delegates document shape to Log model so the handler writes 06-DB-004–compliant documents.
 */
final class LogMongoFormatter implements FormatterInterface
{
    /**
     * @return array<string, mixed>
     */
    public function format(LogRecord $record): array
    {
        return Log::fromMonologRecord($record);
    }

    /**
     * @param  LogRecord[]  $records
     * @return array<int, array<string, mixed>>
     */
    public function formatBatch(array $records): array
    {
        $formatted = [];
        foreach ($records as $record) {
            $formatted[] = Log::fromMonologRecord($record);
        }

        return $formatted;
    }
}
