<?php

namespace Modules\Udemy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Udemy\Models\UserToken;

class UserTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Udemy\Models\UserToken::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'token' => $this->faker->uuid,
            'name' => $this->faker->name(),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (UserToken $user) {
            // ...
        })->afterCreating(function (UserToken $user) {

        });
    }
}

