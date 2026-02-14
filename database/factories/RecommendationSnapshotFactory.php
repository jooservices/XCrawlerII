<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Mongo\RecommendationSnapshot;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\Mongo\RecommendationSnapshot>
 */
class RecommendationSnapshotFactory extends Factory
{
    protected $model = RecommendationSnapshot::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'generated_at' => now(),
            'payload' => [
                'recommendations' => [
                    ['code' => fake()->regexify('[A-Z]{3,5}-[0-9]{3,4}'), 'score' => fake()->randomFloat(3, 0.1, 1.0)],
                    ['code' => fake()->regexify('[A-Z]{3,5}-[0-9]{3,4}'), 'score' => fake()->randomFloat(3, 0.1, 1.0)],
                ],
            ],
        ];
    }
}
