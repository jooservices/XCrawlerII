<?php

declare(strict_types=1);

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\MongoDb\ClientLog;
use MongoDB\BSON\UTCDateTime;

/**
 * @extends Factory<ClientLog>
 */
final class ClientLogFactory extends Factory
{
    protected $model = ClientLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $attempt = $this->faker->numberBetween(1, 3);
        $status = $this->faker->numberBetween(200, 599);

        return [
            'ts' => new UTCDateTime,
            'site' => $this->faker->domainName(),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
            'path' => '/'.$this->faker->slug(),
            'url' => $this->faker->url(),
            'status' => $status,
            'ok' => $status >= 200 && $status < 400,
            'duration_ms' => $this->faker->numberBetween(1, 5000),
            'attempt' => $attempt,
            'retries' => $attempt - 1,
            'max_attempts' => 3,
            'request' => [
                'headers' => ['accept' => 'application/json'],
                'body_preview' => ['q' => $this->faker->word()],
                'body_sha1' => sha1($this->faker->sentence()),
                'body_truncated' => false,
                'size_bytes' => 32,
            ],
            'response' => [
                'headers' => ['content-type' => 'application/json'],
                'body_preview' => ['items' => []],
                'body_sha1' => sha1($this->faker->sentence()),
                'body_truncated' => false,
                'size_bytes' => 64,
            ],
            'cache' => [
                'enabled' => $this->faker->boolean(),
                'hit' => $this->faker->boolean(),
                'key' => $this->faker->sha1(),
                'ttl_sec' => 300,
                'store' => 'database',
            ],
            'error' => null,
            'correlation_id' => $this->faker->uuid(),
            'trace_id' => $this->faker->uuid(),
            'tags' => ['crawler', 'test'],
            'task_id' => 'task_'.$this->faker->uuid(),
            'job_id' => 'job_'.$this->faker->uuid(),
        ];
    }
}
