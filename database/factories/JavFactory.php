<?php

namespace Database\Factories;

use Modules\JAV\Models\Jav;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\Jav>
 */
class JavFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Jav::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'item_id' => fake()->unique()->numerify('item-####'),
            'code' => fake()->regexify('[A-Z]{3,5}-[0-9]{3,4}'),
            'title' => fake()->sentence(),
            'url' => fake()->url(),
            'image' => fake()->imageUrl(300, 400, 'movies', true),
            'date' => fake()->dateTimeBetween('-2 years', 'now'),
            'size' => fake()->randomFloat(2, 0.5, 5.0),
            'description' => fake()->optional(0.7)->paragraph(),
            'download' => fake()->optional(0.5)->url(),
            'source' => fake()->randomElement(['source1', 'source2', 'source3']),
            'views' => fake()->numberBetween(0, 10000),
            'downloads' => fake()->numberBetween(0, 1000),
        ];
    }

    /**
     * Indicate that the model should be popular.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views' => fake()->numberBetween(10000, 100000),
            'downloads' => fake()->numberBetween(1000, 10000),
        ]);
    }

    /**
     * Indicate that the model is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
