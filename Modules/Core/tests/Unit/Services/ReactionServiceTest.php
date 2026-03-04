<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Reaction;
use Modules\Core\Repositories\ReactionRepository;
use Modules\Core\Services\ReactionService;
use Modules\Core\Tests\TestCase;

final class ReactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReactionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReactionService(new ReactionRepository());
    }

    public function test_create_with_positive_delta(): void
    {
        $result = $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-1',
            reaction: 'like',
            delta: 5,
        );

        $this->assertSame('Modules\\JAV\\Models\\Movie', $result['reactable_type']);
        $this->assertSame('movie-1', $result['reactable_id']);
        $this->assertSame('like', $result['reaction']);
        $this->assertSame(5, $result['count']);
        $this->assertSame(5, $result['delta']);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-1',
            'reaction' => 'like',
            'count' => 5,
        ]);
    }

    public function test_create_with_negative_delta_clamps_to_zero(): void
    {
        $result = $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-2',
            reaction: 'like',
            delta: -1,
        );

        $this->assertSame(0, $result['count']);
        $this->assertSame(-1, $result['delta']);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-2',
            'reaction' => 'like',
            'count' => 0,
        ]);
    }

    public function test_increment_existing_count(): void
    {
        $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-3',
            reaction: 'like',
            delta: 5,
        );

        $result = $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-3',
            reaction: 'like',
            delta: 5,
        );

        $this->assertSame(10, $result['count']);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-3',
            'reaction' => 'like',
            'count' => 10,
        ]);
    }

    public function test_decrement_existing_count(): void
    {
        $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-4',
            reaction: 'like',
            delta: 5,
        );

        $result = $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-4',
            reaction: 'like',
            delta: -1,
        );

        $this->assertSame(4, $result['count']);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-4',
            'reaction' => 'like',
            'count' => 4,
        ]);
    }

    public function test_decrement_never_goes_below_zero(): void
    {
        $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-5',
            reaction: 'like',
            delta: 5,
        );

        for ($i = 0; $i < 8; $i++) {
            $result = $this->service->react(
                reactionType: 'Modules\\JAV\\Models\\Movie',
                reactionId: 'movie-5',
                reaction: 'like',
                delta: -1,
            );
        }

        $this->assertSame(0, $result['count']);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-5',
            'reaction' => 'like',
            'count' => 0,
        ]);
    }

    public function test_reaction_types_are_isolated(): void
    {
        $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-6',
            reaction: 'like',
            delta: 5,
        );

        $resultDislike = $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-6',
            reaction: 'dislike',
            delta: 5,
        );

        $this->assertSame('dislike', $resultDislike['reaction']);
        $this->assertSame(5, $resultDislike['count']);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-6',
            'reaction' => 'like',
            'count' => 5,
        ]);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-6',
            'reaction' => 'dislike',
            'count' => 5,
        ]);
    }

    public function test_targets_are_isolated(): void
    {
        $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-7-a',
            reaction: 'like',
            delta: 5,
        );

        $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-7-b',
            reaction: 'like',
            delta: 5,
        );

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-7-a',
            'reaction' => 'like',
            'count' => 5,
        ]);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-7-b',
            'reaction' => 'like',
            'count' => 5,
        ]);
    }

    public function test_zero_delta_keeps_count_unchanged(): void
    {
        $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-8',
            reaction: 'like',
            delta: 5,
        );

        $result = $this->service->react(
            reactionType: 'Modules\\JAV\\Models\\Movie',
            reactionId: 'movie-8',
            reaction: 'like',
            delta: 0,
        );

        $this->assertSame(5, $result['count']);
        $this->assertSame(0, $result['delta']);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-8',
            'reaction' => 'like',
            'count' => 5,
        ]);
    }
}
