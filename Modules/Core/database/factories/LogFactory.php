<?php

declare(strict_types=1);

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\MongoDb\Log;
use MongoDB\BSON\UTCDateTime;

/**
 * @extends Factory<Log>
 */
final class LogFactory extends Factory
{
    protected $model = Log::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ts = new UTCDateTime;

        return [
            'message' => $this->faker->sentence(),
            'level' => $this->faker->numberBetween(100, 600),
            'level_name' => $this->faker->randomElement(['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']),
            'channel' => $this->faker->word(),
            'context' => [],
            'extra' => [],
            'datetime' => $ts,
            'schema_version' => Log::SCHEMA_VERSION,
            'created_at' => $ts,
            'updated_at' => $ts,
        ];
    }
}
