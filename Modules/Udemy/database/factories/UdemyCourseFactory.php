<?php

namespace Modules\Udemy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Udemy\Models\UdemyCourse;

class UdemyCourseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = UdemyCourse::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->numerify(),
            'is_course_available_in_org' => $this->faker->boolean,
            'is_practice_test_course' => $this->faker->boolean,
            'is_private' => $this->faker->boolean,
            'is_published' => $this->faker->boolean,
            'published_title' => $this->faker->text,
            'title' => $this->faker->text,
            'url' => $this->faker->url,
            'class' => $this->faker->text,
        ];
    }
}

