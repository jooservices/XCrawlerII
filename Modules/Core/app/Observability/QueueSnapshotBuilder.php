<?php

namespace Modules\Core\Observability;

use Illuminate\Support\Facades\Queue;
use Throwable;

class QueueSnapshotBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(array $context, int $jobsPerSecond): array
    {
        $queue = (string) ($context['queue'] ?? 'default');
        $connection = (string) ($context['connection'] ?? config('queue.default', 'redis'));

        return [
            'queue' => $queue,
            'connection' => $connection,
            'site' => $context['site'] ?? null,
            'worker_host' => $context['worker_host'] ?? null,
            'job_name' => $context['job_name'] ?? null,
            'jobs_per_second' => $jobsPerSecond,
            'queue_depth' => $this->queueDepth($queue, $connection),
        ];
    }

    private function queueDepth(string $queue, string $connection): ?int
    {
        try {
            $size = Queue::connection($connection)->size($queue);
        } catch (Throwable) {
            return null;
        }

        if (! is_numeric($size)) {
            return null;
        }

        return max(0, (int) $size);
    }
}
