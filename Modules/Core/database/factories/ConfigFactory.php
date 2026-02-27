<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\MongoDb\Config;

class ConfigFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Config::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group' => $this->faker->word(),
            'key' => $this->faker->unique()->word(),
            'value' => $this->faker->sentence(),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
