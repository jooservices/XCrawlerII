<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserJavHistory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\UserJavHistory>
 */
class UserJavHistoryFactory extends Factory
{
    protected $model = UserJavHistory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'jav_id' => Jav::factory(),
            'action' => fake()->randomElement(['view', 'download']),
        ];
    }
}
