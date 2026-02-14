<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Watchlist;

class WatchlistFactory extends Factory
{
    protected $model = Watchlist::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'jav_id' => \Modules\JAV\Models\Jav::factory(),
            'status' => fake()->randomElement(['to_watch', 'watching', 'watched']),
        ];
    }
}
