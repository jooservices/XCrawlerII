<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserLikeNotification;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\UserLikeNotification>
 */
class UserLikeNotificationFactory extends Factory
{
    protected $model = UserLikeNotification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'jav_id' => Jav::factory(),
            'dedupe_key' => fake()->unique()->uuid(),
            'title' => fake()->sentence(4),
            'message' => fake()->sentence(),
            'payload' => [
                'code' => fake()->regexify('[A-Z]{3,5}-[0-9]{3,4}'),
                'source' => fake()->randomElement(['onejav', '141jav', 'xcity']),
            ],
            'read_at' => fake()->optional(0.2)->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
