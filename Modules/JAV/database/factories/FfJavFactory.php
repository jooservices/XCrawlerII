<?php

declare(strict_types=1);

namespace Modules\JAV\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\MongoDb\FfJav;

/**
 * @extends Factory<FfJav>
 */
final class FfJavFactory extends Factory
{
    protected $model = FfJav::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->bothify('FF-###')),
            'movie' => ['title' => $this->faker->sentence(2)],
            'tags' => [],
            'actors' => [],
        ];
    }
}
