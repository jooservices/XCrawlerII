<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;

class Health
{
    /**
     * @return array{healthy: bool, checks: array<int, array{service: string, status: string, detail: string}>}
     */
    public function check(): array
    {
        $checks = [
            $this->checkMariaDb(),
            $this->checkMongoDb(),
            $this->checkRedis(),
            $this->checkElasticsearch(),
        ];

        $healthy = collect($checks)->every(fn (array $check): bool => $check['status'] === 'OK');

        return [
            'healthy' => $healthy,
            'checks' => $checks,
        ];
    }

    public function assertHealthy(): void
    {
        if (config('app.env') === 'testing') {
            return;
        }

        $result = $this->check();
        if ($result['healthy']) {
            return;
        }

        $failedChecks = collect($result['checks'])
            ->filter(fn (array $check): bool => $check['status'] === 'FAIL')
            ->map(fn (array $check): string => $check['service'].': '.$check['detail'])
            ->implode('; ');

        throw new \RuntimeException('Startup service health check failed: '.$failedChecks);
    }

    /**
     * @return array{service: string, status: string, detail: string}
     */
    protected function checkMariaDb(): array
    {
        return $this->runCheck('mariadb', function (): string {
            DB::connection('mariadb')->select('SELECT 1');

            return 'connection ok';
        });
    }

    /**
     * @return array{service: string, status: string, detail: string}
     */
    protected function checkMongoDb(): array
    {
        return $this->runCheck('mongodb', function (): string {
            $database = (string) config('database.connections.mongodb.database', 'xcrawler');
            $uri = (string) config('database.connections.mongodb.dsn', 'mongodb://127.0.0.1:27017');
            $manager = new Manager($uri);
            $manager->executeCommand($database, new Command(['ping' => 1]));

            return 'ping ok';
        });
    }

    /**
     * @return array{service: string, status: string, detail: string}
     */
    protected function checkRedis(): array
    {
        return $this->runCheck('redis', function (): string {
            $pong = Redis::connection('default')->ping();
            $detail = is_scalar($pong) ? (string) $pong : 'pong';

            return 'ping '.$detail;
        });
    }

    /**
     * @return array{service: string, status: string, detail: string}
     */
    protected function checkElasticsearch(): array
    {
        $url = (string) config('services.elasticsearch.url', '');
        if ($url === '') {
            return [
                'service' => 'elasticsearch',
                'status' => 'FAIL',
                'detail' => 'missing ELASTICSEARCH_URL',
            ];
        }

        return $this->runCheck('elasticsearch', function () use ($url): string {
            $response = Http::connectTimeout(2)->timeout(5)->get($url);
            if (! $response->successful()) {
                return 'http '.$response->status();
            }

            $clusterName = (string) $response->json('cluster_name', '');

            return $clusterName === '' ? 'http 200' : 'cluster '.$clusterName;
        }, strictSuccess: true);
    }

    /**
     * @param  callable(): string  $check
     * @return array{service: string, status: string, detail: string}
     */
    protected function runCheck(string $service, callable $check, bool $strictSuccess = false): array
    {
        try {
            $detail = $check();
            if ($strictSuccess && str_starts_with($detail, 'http ') && $detail !== 'http 200') {
                return [
                    'service' => $service,
                    'status' => 'FAIL',
                    'detail' => $detail,
                ];
            }

            return [
                'service' => $service,
                'status' => 'OK',
                'detail' => $detail,
            ];
        } catch (\Throwable $throwable) {
            return [
                'service' => $service,
                'status' => 'FAIL',
                'detail' => $throwable->getMessage(),
            ];
        }
    }
}
