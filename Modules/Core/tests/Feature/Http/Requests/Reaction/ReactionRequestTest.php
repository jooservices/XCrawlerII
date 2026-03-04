<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Http\Requests\Reaction;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ReactionRequestTest extends TestCase
{
    use RefreshDatabase;

    private const string URI = '/api/v1/reactions';

    #[Test]
    public function test_required_fields(): void
    {
        $this->postJson(self::URI, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'reactable_type',
                'reactable_id',
                'reaction',
                'delta',
            ]);
    }

    #[Test]
    public function test_allowed_reaction_values(): void
    {
        $this->postJson(self::URI, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-reaction-invalid',
            'reaction' => 'rocket',
            'delta' => 5,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reaction']);
    }

    #[Test]
    public function test_allowed_delta_values(): void
    {
        $this->postJson(self::URI, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-delta-invalid',
            'reaction' => 'like',
            'delta' => 2,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['delta']);
    }

    #[Test]
    public function test_delta_minus_one_accepted(): void
    {
        $this->postJson(self::URI, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-delta-minus-one',
            'reaction' => 'like',
            'delta' => -1,
        ])
            ->assertOk()
            ->assertJsonPath('data.delta', -1);
    }

    #[Test]
    public function test_delta_five_accepted(): void
    {
        $this->postJson(self::URI, [
            'reactable_type' => 'Modules\\JAV\\Models\\Movie',
            'reactable_id' => 'movie-delta-five',
            'reaction' => 'like',
            'delta' => 5,
        ])
            ->assertOk()
            ->assertJsonPath('data.delta', 5);
    }

    #[Test]
    public function test_max_length_constraints(): void
    {
        $this->postJson(self::URI, [
            'reactable_type' => str_repeat('A', 256),
            'reactable_id' => str_repeat('B', 256),
            'reaction' => str_repeat('x', 17),
            'delta' => 5,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reactable_type', 'reactable_id', 'reaction']);
    }

    #[Test]
    public function test_input_normalization(): void
    {
        $this->postJson(self::URI, [
            'reactable_type' => '  Modules\\JAV\\Models\\Movie  ',
            'reactable_id' => '  movie-normalized  ',
            'reaction' => '  LIKE  ',
            'delta' => '5',
        ])
            ->assertOk()
            ->assertJsonPath('data.reactable_type', 'Modules\\JAV\\Models\\Movie')
            ->assertJsonPath('data.reactable_id', 'movie-normalized')
            ->assertJsonPath('data.reaction', 'like')
            ->assertJsonPath('data.delta', 5);
    }
}
