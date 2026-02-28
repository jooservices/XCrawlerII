<?php

declare(strict_types=1);

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\MongoDb\EventStore;
use MongoDB\BSON\UTCDateTime;

/**
 * @extends Factory<EventStore>
 */
final class EventStoreFactory extends Factory
{
    protected $model = EventStore::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $occurred = $this->faker->dateTimeBetween('-1 year', 'now');

        return [
            'event_id' => $this->faker->uuid(),
            'event_name' => 'domain.event.'.$this->faker->slug(2),
            'occurred_at' => new UTCDateTime($occurred),
            'aggregate_type' => $this->faker->word().'_aggregate',
            'aggregate_id' => $this->faker->uuid(),
            'aggregate_version' => $this->faker->optional(0.7)->numberBetween(1, 100),
            'payload' => [
                'key' => $this->faker->word(),
                'value' => $this->faker->sentence(),
            ],
            'correlation_id' => $this->faker->optional(0.6)->uuid(),
            'causation_id' => $this->faker->optional(0.4)->uuid(),
            'actor_type' => $this->faker->randomElement(['user', 'system', 'api', 'console']),
            'actor_id' => $this->faker->optional(0.7)->uuid(),
        ];
    }
}
