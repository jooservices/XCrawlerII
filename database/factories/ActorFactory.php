<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\JAV\Models\Actor;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\Actor>
 */
class ActorFactory extends Factory
{
    protected $model = Actor::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->unique()->name(),
            'xcity_id' => fake()->optional()->numerify('#######'),
            'xcity_url' => fake()->optional()->url(),
            'xcity_cover' => fake()->optional()->imageUrl(300, 400, 'people', true),
            'xcity_birth_date' => fake()->optional()->date(),
            'xcity_blood_type' => fake()->optional()->randomElement(['A', 'B', 'AB', 'O']),
            'xcity_city_of_birth' => fake()->optional()->city(),
            'xcity_height' => fake()->optional()->numerify('1## cm'),
            'xcity_size' => fake()->optional()->bothify('B## / W## / H##'),
            'xcity_hobby' => fake()->optional()->sentence(3),
            'xcity_special_skill' => fake()->optional()->sentence(3),
            'xcity_other' => fake()->optional()->sentence(),
            'xcity_profile' => fake()->optional()->passthrough([
                'debut' => fake()->year(),
                'language' => fake()->languageCode(),
                'agency' => fake()->company(),
            ]),
            'xcity_synced_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
