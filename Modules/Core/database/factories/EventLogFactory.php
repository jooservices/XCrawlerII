<?php

declare(strict_types=1);

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\MongoDb\EventLog;
use MongoDB\BSON\UTCDateTime;

/**
 * @extends Factory<EventLog>
 */
final class EventLogFactory extends Factory
{
    protected $model = EventLog::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $occurred = $this->faker->dateTimeBetween('-1 year', 'now');
        $changed = ['name', 'status'];

        return [
            'event_id' => $this->faker->uuid(),
            'event_name' => 'entity.updated',
            'occurred_at' => new UTCDateTime($occurred),
            'entity_type' => $this->faker->word() . '_entity',
            'entity_id' => $this->faker->uuid(),
            'changed_fields' => $changed,
            'previous' => ['name' => $this->faker->name(), 'status' => 'old'],
            'new' => ['name' => $this->faker->name(), 'status' => 'new'],
            'correlation_id' => $this->faker->optional(0.6)->uuid(),
            'actor_type' => 'user',
            'actor_id' => $this->faker->uuid(),
        ];
    }
}
