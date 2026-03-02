<?php

declare(strict_types=1);

namespace Modules\JAV\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Actor;

/**
 * @extends Factory<Actor>
 */
class ActorFactory extends Factory
{
    protected $model = Actor::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'avatar' => $this->faker->optional()->imageUrl(),
            'aliases' => null,
            'birth_date' => $this->faker->optional()->date(),
            'birthplace' => $this->faker->optional()->city(),
            'blood_type' => $this->faker->optional()->randomElement(['A', 'B', 'AB', 'O']),
            'height' => $this->faker->optional()->numberBetween(150, 180),
            'weight' => $this->faker->optional()->numberBetween(40, 70),
            'bust' => $this->faker->optional()->numberBetween(80, 100),
            'waist' => $this->faker->optional()->numberBetween(55, 70),
            'hip' => $this->faker->optional()->numberBetween(85, 100),
            'cup_size' => $this->faker->optional()->randomElement(['A', 'B', 'C', 'D']),
            'hobbies' => null,
            'skills' => null,
            'attributes' => null,
            'crawled_at' => $this->faker->optional()->dateTime(),
            'seen_at' => null,
        ];
    }
}
