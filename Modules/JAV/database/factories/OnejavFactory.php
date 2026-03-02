<?php

declare(strict_types=1);

namespace Modules\JAV\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\MongoDb\Onejav;

/**
 * @extends Factory<Onejav>
 */
final class OnejavFactory extends Factory
{
    protected $model = Onejav::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->bothify('JAV-###')),
            'movie' => ['title' => $this->faker->sentence(2)],
            'tags' => [],
            'actors' => [],
        ];
    }
}
