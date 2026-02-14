<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\ActorProfileSource;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\JAV\Models\ActorProfileSource>
 */
class ActorProfileSourceFactory extends Factory
{
    protected $model = ActorProfileSource::class;

    public function definition(): array
    {
        return [
            'actor_id' => Actor::factory(),
            'source' => fake()->lexify('source-????'),
            'source_actor_id' => fake()->numerify('id-########'),
            'source_url' => fake()->optional()->url(),
            'source_cover' => fake()->optional()->imageUrl(300, 400, 'people', true),
            'payload' => fake()->optional()->passthrough([
                'name' => fake()->name(),
                'bio' => fake()->sentence(),
                'updated_at' => now()->toDateTimeString(),
            ]),
            'is_primary' => fake()->boolean(15),
            'fetched_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
            'synced_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
