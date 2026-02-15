<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Rating;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\Rating>
 */
class RatingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Rating::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'jav_id' => Jav::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'review' => fake()->optional(0.6)->paragraph(),
        ];
    }

    /**
     * Indicate that the rating has no review.
     */
    public function withoutReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'review' => null,
        ]);
    }

    /**
     * Indicate a specific star rating.
     */
    public function stars(int $stars): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $stars,
        ]);
    }
}
