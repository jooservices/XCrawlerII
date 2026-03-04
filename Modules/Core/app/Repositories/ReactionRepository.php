<?php

declare(strict_types=1);

namespace Modules\Core\Repositories;

use Modules\Core\Models\Reaction;

final class ReactionRepository
{
    public function updateOrCreate(string $reactionType, string $reactionId, string $reaction): Reaction
    {
        /** @var Reaction $record */
        $record = Reaction::query()->firstOrCreate([
            'reactable_type' => $reactionType,
            'reactable_id' => $reactionId,
            'reaction' => $reaction,
        ], [
            'count' => 0,
        ]);

        return $record;
    }

    public function adjustCount(string $reactionType, string $reactionId, string $reaction, int $delta): Reaction
    {
        /** @var Reaction|null $record */
        $record = Reaction::query()
            ->where('reactable_type', $reactionType)
            ->where('reactable_id', $reactionId)
            ->where('reaction', $reaction)
            ->lockForUpdate()
            ->first();

        if (! $record instanceof Reaction) {
            $record = Reaction::query()->create([
                'reactable_type' => $reactionType,
                'reactable_id' => $reactionId,
                'reaction' => $reaction,
                'count' => max(0, $delta),
            ]);

            return $record;
        }

        $record->count = max(0, (int) $record->count + $delta);
        $record->save();

        return $record;
    }
}
