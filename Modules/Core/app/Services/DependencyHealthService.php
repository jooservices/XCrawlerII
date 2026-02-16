<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Throwable;

class DependencyHealthService
{
    /**
     * @param  array<int, string>  $dependencies
     * @return array<string, array<string, mixed>>
     */
    public function collect(array $dependencies = []): array
    {
        $configuredDependencies = (array) config('services.obs.dependency_health.dependencies', ['mysql', 'redis', 'elasticsearch', 'mongodb']);
        $targets = $dependencies === [] ? $configuredDependencies : $dependencies;

        $results = [];
        foreach ($this->normalizeDependencies($targets) as $dependency) {
            $results[$dependency] = $this->probe($dependency);
        }

        return $results;
    }

    /**
     * @param  array<int, string>  $dependencies
     * @return array<int, string>
     */
    private function normalizeDependencies(array $dependencies): array
    {
        $normalized = [];

        foreach ($dependencies as $dependency) {
            $name = strtolower(trim((string) $dependency));
            if ($name === '' || in_array($name, $normalized, true)) {
                continue;
            }

            $normalized[] = $name;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function probe(string $dependency): array
    {
        return match ($dependency) {
            'mysql' => $this->probeMysql(),
            'redis' => $this->probeRedis(),
            'elasticsearch' => $this->probeElasticsearch(),
            'mongodb' => $this->probeMongodb(),
            default => $this->unknownDependency($dependency),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function probeMysql(): array
    {
        return $this->timedProbe('mysql', function (): void {
            DB::connection('mysql')->select('SELECT 1');
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function probeRedis(): array
    {
        return $this->timedProbe('redis', function (): void {
            Redis::connection()->ping();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function probeElasticsearch(): array
    {
        return $this->timedProbe('elasticsearch', function (): void {
            $host = $this->normalizeElasticsearchHost((string) config('scout.elasticsearch.hosts.0', env('ELASTICSEARCH_HOST', 'http://127.0.0.1:9200')));

            Http::acceptJson()
                ->timeout(2)
                ->get(rtrim($host, '/').'/')
                ->throw();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function probeMongodb(): array
    {
        return $this->timedProbe('mongodb', function (): void {
            DB::connection('mongodb')->getPdo();
        });
    }

    /**
     * @param  callable(): void  $probe
     * @return array<string, mixed>
     */
    private function timedProbe(string $dependency, callable $probe): array
    {
        $startedAt = microtime(true);
        $status = 'up';
        $error = null;

        try {
            $probe();
        } catch (Throwable $exception) {
            $status = 'down';
            $error = mb_substr($exception->getMessage(), 0, 500);
        }

        return [
            'dependency' => $dependency,
            'status' => $status,
            'latency_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            'error' => $error,
            'checked_at' => now('UTC')->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unknownDependency(string $dependency): array
    {
        return [
            'dependency' => $dependency,
            'status' => 'unknown',
            'latency_ms' => null,
            'error' => 'Unsupported dependency target',
            'checked_at' => now('UTC')->toIso8601String(),
        ];
    }

    private function normalizeElasticsearchHost(string $host): string
    {
        $normalized = trim($host);
        if ($normalized === '') {
            return 'http://127.0.0.1:9200';
        }

        if (! str_starts_with($normalized, 'http://') && ! str_starts_with($normalized, 'https://')) {
            return 'http://'.$normalized;
        }

        return $normalized;
    }
}
