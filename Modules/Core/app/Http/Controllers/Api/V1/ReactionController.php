<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\AbstractApiController;
use Modules\Core\Http\Requests\Reaction\ReactionRequest;
use Modules\Core\Services\ReactionService;

final class ReactionController extends AbstractApiController
{
    public function __construct(private readonly ReactionService $reactionService)
    {
    }

    public function react(ReactionRequest $request): JsonResponse
    {
        $payload = $request->validated();

        return $this->ok($this->reactionService->react(
            reactionType: (string) $payload['reactable_type'],
            reactionId: (string) $payload['reactable_id'],
            reaction: (string) $payload['reaction'],
            delta: (int) $payload['delta'],
        ));
    }
}
