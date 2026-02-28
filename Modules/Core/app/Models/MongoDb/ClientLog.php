<?php

declare(strict_types=1);

namespace Modules\Core\Models\MongoDb;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Database\Factories\ClientLogFactory;
use Modules\Core\Models\MongoDb;
use MongoDB\BSON\UTCDateTime;

/**
 * @property array<string, mixed> $attributes
 */
final class ClientLog extends MongoDb
{
    use HasFactory;

    public const string COLLECTION = 'client_logs';

    protected $table = self::COLLECTION;

    protected $fillable = [
        'ts',
        'site',
        'method',
        'path',
        'url',
        'status',
        'ok',
        'duration_ms',
        'attempt',
        'retries',
        'max_attempts',
        'request',
        'response',
        'cache',
        'error',
        'correlation_id',
        'trace_id',
        'tags',
        'task_id',
        'job_id',
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function fromHttpLifecycle(array $payload): array
    {
        $ts = $payload['ts'] ?? null;
        $timestamp = new UTCDateTime();
        if ($ts instanceof UTCDateTime) {
            $timestamp = $ts;
        }
        if ($ts instanceof \DateTimeInterface) {
            $timestamp = new UTCDateTime($ts);
        }

        $attempt = max(1, (int) ($payload['attempt'] ?? 1));
        $maxAttempts = max($attempt, (int) ($payload['max_attempts'] ?? $attempt));
        $status = (int) ($payload['status'] ?? 0);

        return [
            'ts' => $timestamp,
            'site' => (string) ($payload['site'] ?? ''),
            'method' => strtoupper((string) ($payload['method'] ?? 'GET')),
            'path' => (string) ($payload['path'] ?? ''),
            'url' => (string) ($payload['url'] ?? ''),
            'status' => $status,
            'ok' => $status >= 200 && $status < 400,
            'duration_ms' => max(0, (int) ($payload['duration_ms'] ?? 0)),
            'attempt' => $attempt,
            'retries' => max(0, $attempt - 1),
            'max_attempts' => $maxAttempts,
            'request' => is_array($payload['request'] ?? null) ? $payload['request'] : [],
            'response' => is_array($payload['response'] ?? null) ? $payload['response'] : [],
            'cache' => is_array($payload['cache'] ?? null) ? $payload['cache'] : [],
            'error' => is_array($payload['error'] ?? null) ? $payload['error'] : null,
            'correlation_id' => $payload['correlation_id'] ?? null,
            'trace_id' => $payload['trace_id'] ?? null,
            'tags' => is_array($payload['tags'] ?? null) ? $payload['tags'] : [],
            'task_id' => $payload['task_id'] ?? null,
            'job_id' => $payload['job_id'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDocument(): array
    {
        return $this->attributes;
    }

    protected static function newFactory(): ClientLogFactory
    {
        return ClientLogFactory::new();
    }
}
