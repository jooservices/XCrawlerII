<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Tag;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
