<?php

namespace Modules\Jav\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class JavGenreFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Jav\Models\JavGenre::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}
