<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Mongo\AnalyticsSnapshot;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\Mongo\AnalyticsSnapshot>
 */
class AnalyticsSnapshotFactory extends Factory
{
    protected $model = AnalyticsSnapshot::class;

    public function definition(): array
    {
        return [
            'days' => fake()->randomElement([1, 7, 30, 90]),
            'generated_at' => now(),
            'payload' => [
                'items' => fake()->numberBetween(0, 1000),
                'views' => fake()->numberBetween(0, 100000),
                'downloads' => fake()->numberBetween(0, 20000),
            ],
        ];
    }
}
