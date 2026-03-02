<?php

declare(strict_types=1);

namespace Modules\JAV\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\MongoDb\OneFourOneJav;

/**
 * @extends Factory<OneFourOneJav>
 */
final class OneFourOneJavFactory extends Factory
{
    protected $model = OneFourOneJav::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->bothify('141-###')),
            'movie' => ['title' => $this->faker->sentence(2)],
            'tags' => [],
            'actors' => [],
        ];
    }
}
