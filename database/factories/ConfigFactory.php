<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Config;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Core\Models\Config>
 */
class ConfigFactory extends Factory
{
    protected $model = Config::class;

    public function definition(): array
    {
        return [
            'group' => fake()->randomElement(['app', 'cache', 'crawler', 'jav', 'service']),
            'key' => fake()->unique()->slug(2),
            'value' => fake()->optional()->sentence(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
