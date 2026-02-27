<?php

declare(strict_types=1);

namespace Modules\Core\Services\Client\Logging;

use Modules\Core\Models\ClientLog;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;

final class MongoHttpLogWriter
{
    private Manager $manager;

    public function __construct(
        string $uri,
        private readonly string $database,
    ) {
        $this->manager = new Manager($uri);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function write(array $payload): void
    {
        $document = ClientLog::fromHttpLifecycle($payload);

        $bulk = new BulkWrite();
        $bulk->insert($document);

        $this->manager->executeBulkWrite($this->database.'.'.ClientLog::COLLECTION, $bulk);
    }
}
