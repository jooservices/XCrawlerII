<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Integration\Reaction;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Reaction;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ReactionFlowTest extends TestCase
{
    use RefreshDatabase;

    private const string REACTABLE_TYPE = 'Modules\\JAV\\Models\\Movie';

    private const string REACTABLE_ID = 'movie-1';

    private const string REACTION = 'like';

    #[Test]
    public function it_writes_reaction_counts_directly_to_db(): void
    {
        $increment = $this->postJson('/api/v1/reactions', [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'delta' => 5,
        ]);

        $increment->assertOk()
            ->assertJsonPath('data.count', 5)
            ->assertJsonPath('data.reaction', 'like');

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'count' => 5,
        ]);

        $decrement = $this->postJson('/api/v1/reactions', [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'delta' => -1,
        ]);

        $decrement->assertOk()
            ->assertJsonPath('data.count', 4)
            ->assertJsonPath('data.delta', -1);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'count' => 4,
        ]);
    }

    #[Test]
    public function it_never_goes_below_zero_when_decrementing_more_than_current_count(): void
    {
        $this->postJson('/api/v1/reactions', [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'delta' => 5,
        ])->assertOk();

        $this->postJson('/api/v1/reactions', [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'delta' => -1,
        ])->assertOk()->assertJsonPath('data.count', 4);

        $this->postJson('/api/v1/reactions', [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'delta' => -1,
        ])->assertOk()->assertJsonPath('data.count', 3);

        $this->postJson('/api/v1/reactions', [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'delta' => -1,
        ])->assertOk()->assertJsonPath('data.count', 2);

        $this->postJson('/api/v1/reactions', [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'delta' => -1,
        ])->assertOk()->assertJsonPath('data.count', 1);

        $this->postJson('/api/v1/reactions', [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'delta' => -1,
        ])->assertOk()->assertJsonPath('data.count', 0);

        $this->postJson('/api/v1/reactions', [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'delta' => -1,
        ])->assertOk()->assertJsonPath('data.count', 0);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'count' => 0,
        ]);
    }

    #[Test]
    public function it_rejects_invalid_delta_value(): void
    {
        $this->postJson('/api/v1/reactions', [
            'reactable_type' => self::REACTABLE_TYPE,
            'reactable_id' => self::REACTABLE_ID,
            'reaction' => self::REACTION,
            'delta' => 0,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['delta']);
    }
}
