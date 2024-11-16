<?php

namespace Modules\Jav\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Jav\Models\JavGenre;

class JavGenreFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = JavGenre::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->name
        ];
    }
}
