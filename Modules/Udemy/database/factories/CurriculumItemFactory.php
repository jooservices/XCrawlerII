<?php

namespace Modules\Udemy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Udemy\Models\UdemyCourse;

class CurriculumItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Udemy\Models\CurriculumItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $course = UdemyCourse::inRandomOrder()->first();
        return [
            'id' => $this->faker->numerify(),
            'course_id' => $course ? $course->id : UdemyCourse::factory()->create([
                'id' => 59583
            ])->id,
            'is_published' => $this->faker->boolean(),
            'title' => $this->faker->sentence(),
            'class'=> $this->faker->randomElement([
                'lecture'
            ]),
            'asset_id'=> $this->faker->numberBetween(1, 10),
            'asset_type'=> $this->faker->word(),
//            'asset_filename',
//            'asset_is_external',
//            'asset_status',
            'asset_time_estimation'=> $this->faker->numberBetween(100, 1000),
//            'asset_title',
//            'asset_class'
        ];
    }
}

