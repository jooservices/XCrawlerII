<?php

namespace Modules\Udemy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;

class UserTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = UserToken::class;

    /**
     * Define the model's default state.
     */
    final public function definition(): array
    {
        return [
            'token' => $this->faker->uuid,
            'name' => $this->faker->name(),
        ];
    }

    /**
     * Configure the model factory.
     */
    final public function configure(): static
    {
        return $this->afterMaking(function (UserToken $user) {
            // ...
        })->afterCreating(function (UserToken $user) {
        });
    }

    /**
     * Indicate that the user is suspended.
     */
    public function withCourse(): Factory
    {
        return $this->state(function (array $attributes) {
            return $attributes;
        })->afterMaking(function (UserToken $user) {
            // ...
        })->afterCreating(function (UserToken $user) {
            $user->courses()->syncWithoutDetaching([
                UdemyCourse::factory()->create([
                    'id' => 59583,
                ])->id,
            ]);
        });
    }
}
