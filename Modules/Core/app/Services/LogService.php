<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Models\MongoDb\Log;
use Modules\Core\Repositories\LogRepository;

/**
 * Get log data via repository (03-BE-001: service uses repository, no direct Model in callers).
 */
final class LogService
{
    public function __construct(
        private readonly LogRepository $logRepository,
    ) {
    }

    public function getRecent(int $limit = 50): Collection
    {
        return $this->logRepository->getRecent($limit);
    }

    /**
     * @return LengthAwarePaginator<int, Log>
     */
    public function getPaginated(int $perPage = 15, ?string $channel = null, ?string $levelName = null): LengthAwarePaginator
    {
        return $this->logRepository->getPaginated($perPage, $channel, $levelName);
    }

    public function findById(string $id): ?Log
    {
        return $this->logRepository->findById($id);
    }
}
