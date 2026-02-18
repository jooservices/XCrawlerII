<?php

namespace Modules\JAV\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\JAV\Services\RecommendationService;

class JavSyncRecommendationsCommand extends Command
{
    protected $signature = 'jav:sync:recommendations
                            {--user-id=* : Sync only specific user IDs}
                            {--limit=30 : Recommendation limit per user}';

    protected $description = 'Sync recommendation snapshots from MySQL into MongoDB.';

    public function handle(RecommendationService $recommendationService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $userIds = collect((array) $this->option('user-id'))
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $synced = 0;

        if ($userIds->isNotEmpty()) {
            foreach ($userIds as $userId) {
                if ($recommendationService->syncSnapshotForUserId($userId, $limit)) {
                    $synced++;
                    $this->info("Synced recommendation snapshot for user {$userId}.");
                }
            }

            $this->info("Recommendation sync completed. Synced users: {$synced}.");

            return self::SUCCESS;
        }

        User::query()
            ->whereHas('interactions', function ($query): void {
                $query->where('action', \Modules\JAV\Models\Interaction::ACTION_FAVORITE);
            })
            ->select('id')
            ->chunkById(200, function ($users) use (&$synced, $limit, $recommendationService): void {
                foreach ($users as $user) {
                    if ($recommendationService->syncSnapshotForUserId((int) $user->id, $limit)) {
                        $synced++;
                    }
                }
            });

        $this->info("Recommendation sync completed. Synced users: {$synced}.");

        return self::SUCCESS;
    }
}
