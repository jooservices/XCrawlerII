<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Repositories\ReactionRepository;

final class ReactionService
{
    public function __construct(private readonly ReactionRepository $reactionRepository)
    {
    }

    /**
     * @return array{reactable_type:string,reactable_id:string,reaction:string,count:int,delta:int}
     */
    public function react(string $reactionType, string $reactionId, string $reaction, int $delta): array
    {
        $record = DB::transaction(function () use ($delta, $reaction, $reactionId, $reactionType) {
            $this->reactionRepository->updateOrCreate($reactionType, $reactionId, $reaction);

            return $this->reactionRepository->adjustCount($reactionType, $reactionId, $reaction, $delta);
        });

        return [
            'reactable_type' => $reactionType,
            'reactable_id' => $reactionId,
            'reaction' => $reaction,
            'count' => (int) $record->count,
            'delta' => $delta,
        ];
    }
}
