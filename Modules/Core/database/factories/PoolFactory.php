<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Pool;
use Modules\Core\Models\Queue;

class PoolFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Pool::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'server_ip' => $this->faker->unique()->localIpv4,
            'server_name' => $this->faker->unique()->word,
            'server_wan_ip' => $this->faker->unique()->ipv4,
            'name' => $this->faker->word,
            'description' => $this->faker->text,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Pool $pool) {
            // ...
        })->afterCreating(function (Pool $pool) {
            Queue::factory()->for($pool)
                ->count($this->faker->numberBetween(1, 10))
                ->create();
        });
    }
}
