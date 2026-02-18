<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\Interaction>
 */
class InteractionFactory extends Factory
{
    protected $model = Interaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'item_type' => Interaction::morphTypeFor(Jav::class),
            'item_id' => Jav::factory(),
            'action' => Interaction::ACTION_FAVORITE,
            'value' => null,
            'meta' => null,
        ];
    }

    public function favorite(): static
    {
        return $this->state(fn () => [
            'action' => Interaction::ACTION_FAVORITE,
            'value' => null,
            'meta' => null,
        ]);
    }

    public function rating(?int $stars = null, ?string $review = null): static
    {
        return $this->state(fn () => [
            'action' => Interaction::ACTION_RATING,
            'value' => $stars ?? $this->faker->numberBetween(1, 5),
            'meta' => $review === null ? null : ['review' => $review],
        ]);
    }

    public function forJav(?Jav $jav = null): static
    {
        return $this->state(fn () => [
            'item_type' => Interaction::morphTypeFor(Jav::class),
            'item_id' => $jav?->id ?? Jav::factory(),
        ]);
    }

    public function forActor(?Actor $actor = null): static
    {
        return $this->state(fn () => [
            'item_type' => Interaction::morphTypeFor(Actor::class),
            'item_id' => $actor?->id ?? Actor::factory(),
        ]);
    }

    public function forTag(?Tag $tag = null): static
    {
        return $this->state(fn () => [
            'item_type' => Interaction::morphTypeFor(Tag::class),
            'item_id' => $tag?->id ?? Tag::factory(),
        ]);
    }
}
