<?php

declare(strict_types=1);

namespace Modules\JAV\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Movie;

/**
 * @extends Factory<Movie>
 */
class MovieFactory extends Factory
{
    protected $model = Movie::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('???-###')),
            'item_id' => $this->faker->optional()->slug(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'category' => $this->faker->optional()->word(),
            'cover' => $this->faker->optional()->imageUrl(),
            'trailer' => $this->faker->optional()->url(),
            'gallery' => null,
            'is_censored' => $this->faker->boolean(),
            'has_subtitles' => $this->faker->boolean(),
            'subtitles' => null,
            'release_date' => $this->faker->optional()->date(),
            'duration_minutes' => $this->faker->optional()->numberBetween(60, 180),
            'crawled_at' => $this->faker->optional()->dateTime(),
            'seen_at' => null,
            'attributes' => null,
        ];
    }
}
