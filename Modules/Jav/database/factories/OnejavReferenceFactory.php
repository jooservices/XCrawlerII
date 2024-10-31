<?php

namespace Modules\Jav\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OnejavReferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Jav\Models\OnejavReference::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'url' => $this->faker->url,
            'cover' => $this->faker->imageUrl,
            'dvd_id' => $this->faker->uuid,
            'size' => $this->faker->randomNumber(),
            'release_date' => $this->faker->date,
            'description' => $this->faker->text,
            'genres' => [
                $this->faker->word,
                $this->faker->word,
                $this->faker->word,
            ],
            'performers' => [
                $this->faker->name,
                $this->faker->name,
                $this->faker->name,
            ]
        ];
    }
}

