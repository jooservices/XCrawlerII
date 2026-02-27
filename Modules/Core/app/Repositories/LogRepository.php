<?php

declare(strict_types=1);

namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Models\MongoDb\Log;

/**
 * 1:1 with Log model (03-BE-003). Read-only persistence for logs collection.
 */
final class LogRepository
{
    public function getRecent(int $limit = 50): Collection
    {
        return Log::query()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, Log>
     */
    public function getPaginated(int $perPage = 15, ?string $channel = null, ?string $levelName = null): LengthAwarePaginator
    {
        $query = Log::query()->orderByDesc('created_at');

        if ($channel !== null && $channel !== '') {
            $query->where('channel', $channel);
        }

        if ($levelName !== null && $levelName !== '') {
            $query->where('level_name', $levelName);
        }

        return $query->paginate($perPage);
    }

    public function findById(string $id): ?Log
    {
        return Log::query()->find($id);
    }
}
