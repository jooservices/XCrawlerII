<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Reaction;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ReactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private const string URI = '/api/v1/reactions';

    #[Test]
    public function test_response_data(): void
    {
        $response = $this->postJson(self::URI, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-100',
            'reaction' => 'like',
            'delta' => 5,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['reactable_type', 'reactable_id', 'reaction', 'count', 'delta'],
                'meta',
            ])
            ->assertJsonPath('data.reactable_type', 'Modules\\JAV\\Models\\Movie')
            ->assertJsonPath('data.reactable_id', 'movie-100')
            ->assertJsonPath('data.reaction', 'like')
            ->assertJsonPath('data.count', 5)
            ->assertJsonPath('data.delta', 5);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-100',
            'reaction' => 'like',
            'count' => 5,
        ]);
    }

    #[Test]
    public function test_validation_error(): void
    {
        $response = $this->postJson(self::URI, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-101',
            'reaction' => 'rocket',
            'delta' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors(['reaction', 'delta']);
    }

    #[Test]
    public function test_input_normalization(): void
    {
        $response = $this->postJson(self::URI, [
            'reactable_type' => '  Modules\\JAV\\Models\\Movie  ',
            'reactable_id' => '  movie-weird  ',
            'reaction' => '  LIKE  ',
            'delta' => '5',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.reactable_type', 'Modules\\JAV\\Models\\Movie')
            ->assertJsonPath('data.reactable_id', 'movie-weird')
            ->assertJsonPath('data.reaction', 'like')
            ->assertJsonPath('data.count', 5)
            ->assertJsonPath('data.delta', 5);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-weird',
            'reaction' => 'like',
            'count' => 5,
        ]);
    }

    #[Test]
    public function test_method_not_allowed(): void
    {
        $this->getJson(self::URI)->assertStatus(405);
    }

    #[Test]
    public function test_injection_like_input(): void
    {
        $injectionLikeId = "movie-1' OR 1=1 --";

        $response = $this->postJson(self::URI, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => $injectionLikeId,
            'reaction' => 'like',
            'delta' => 5,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.reactable_id', $injectionLikeId)
            ->assertJsonPath('data.count', 5);

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => $injectionLikeId,
            'reaction' => 'like',
            'count' => 5,
        ]);
    }

    #[Test]
    public function test_zero_floor_on_decrement(): void
    {
        $payload = [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-abuse-zero-floor',
            'reaction' => 'like',
            'delta' => 5,
        ];

        $this->postJson(self::URI, $payload)
            ->assertOk()
            ->assertJsonPath('data.count', 5);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson(self::URI, [
                ...$payload,
                'delta' => -1,
            ])->assertOk();
        }

        $this->assertDatabaseHas(Reaction::TABLE, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-abuse-zero-floor',
            'reaction' => 'like',
            'count' => 0,
        ]);
    }

    #[Test]
    public function test_oversized_fields_rejected(): void
    {
        $response = $this->postJson(self::URI, [
            'reactable_type' => str_repeat('A', 256),
            'reactable_id' => str_repeat('B', 256),
            'reaction' => 'like',
            'delta' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reactable_type', 'reactable_id']);
    }
}
