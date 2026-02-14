<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\Favorite>
 */
class FavoriteFactory extends Factory
{
    protected $model = Favorite::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'favoritable_id' => Jav::factory(),
            'favoritable_type' => Jav::class,
        ];
    }
}
