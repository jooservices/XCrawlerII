<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\ActorProfileAttribute;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\ActorProfileAttribute>
 */
class ActorProfileAttributeFactory extends Factory
{
    protected $model = ActorProfileAttribute::class;

    public function definition(): array
    {
        $valueString = fake()->optional()->sentence(3);

        return [
            'actor_id' => Actor::factory(),
            'source' => fake()->lexify('source-????'),
            'kind' => fake()->randomElement(['blood_type', 'height', 'hobby', 'city_of_birth']),
            'value_string' => $valueString,
            'value_number' => fake()->optional()->randomFloat(2, 1, 300),
            'value_date' => fake()->optional()->date(),
            'value_label' => fake()->optional()->word(),
            'raw_value' => $valueString,
            'is_primary' => fake()->boolean(15),
            'synced_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
