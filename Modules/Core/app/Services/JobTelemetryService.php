<?php

namespace Modules\Core\Services;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Mongo\JobTelemetryEvent;
use Modules\Core\Observability\Contracts\TelemetryEmitterInterface;
use Modules\Core\Observability\BlockSignalDetector;
use Modules\Core\Observability\QueueSnapshotBuilder;
use Throwable;

class JobTelemetryService
{
    private const TIMER_CACHE_PREFIX = 'job_telemetry:timer';

    private const RATE_CACHE_PREFIX = 'job_telemetry:rate';

    private const RATE_ALERT_CACHE_PREFIX = 'job_telemetry:rate_alert';

    private const SNAPSHOT_CACHE_PREFIX = 'job_telemetry:snapshot';

    private static bool $indexesEnsured = false;

    public function __construct(
        private readonly BlockSignalDetector $blockSignalDetector,
        private readonly QueueSnapshotBuilder $queueSnapshotBuilder,
    ) {}

    public function recordStarted(JobProcessing $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $timestamp = now('UTC');
        $context = $this->buildContext($event->job, $event->connectionName);
        $startedAtMs = $this->nowMs();

        Cache::put(
            $this->timerCacheKey($context),
            $startedAtMs,
            now()->addSeconds((int) config('core.job_telemetry.timer_ttl_seconds', 3600))
        );

        $this->writeEvent([
            'event_type' => 'started',
            'status' => 'running',
            'timestamp' => $timestamp,
            'started_at' => $timestamp,
            'duration_ms' => null,
            ...$context,
        ], $timestamp);

        $this->emitToObs('queue.job.started', [
            ...$context,
            'event_type' => 'started',
            'status' => 'running',
            'timestamp' => $timestamp,
        ], 'info', 'Queue job started');

        $this->trackRate($context, $timestamp);
    }

    public function recordProcessed(JobProcessed $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $timestamp = now('UTC');
        $context = $this->buildContext($event->job, $event->connectionName);
        $duration = $this->computeDurationMs($context);

        $this->writeEvent([
            'event_type' => 'completed',
            'status' => 'success',
            'timestamp' => $timestamp,
            'finished_at' => $timestamp,
            'duration_ms' => $duration,
            ...$context,
        ], $timestamp);

        $this->emitToObs('queue.job.completed', [
            ...$context,
            'event_type' => 'completed',
            'status' => 'success',
            'timestamp' => $timestamp,
            'finished_at' => $timestamp,
            'duration_ms' => $duration,
        ], 'info', 'Queue job completed');
    }

    public function recordFailed(JobFailed $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $timestamp = now('UTC');
        $context = $this->buildContext($event->job, $event->connectionName);
        $duration = $this->computeDurationMs($context);

        $observedTimeout = null;
        if (preg_match('/after\s+(\d+)\s+milliseconds/i', $event->exception->getMessage(), $matches) === 1) {
            $observedTimeout = (int) $matches[1];
        }

        $eventData = [
            'event_type' => 'completed',
            'status' => 'failed',
            'timestamp' => $timestamp,
            'finished_at' => $timestamp,
            'duration_ms' => $duration,
            'error_class' => $event->exception::class,
            'error_code' => is_numeric($event->exception->getCode()) ? (int) $event->exception->getCode() : null,
            'error_message_short' => mb_substr($event->exception->getMessage(), 0, 500),
            'timeout_ms_observed' => $observedTimeout,
            ...$context,
        ];

        $this->writeEvent($eventData, $timestamp);

        $this->emitToObs('queue.job.failed', $eventData, 'error', 'Queue job failed');

        $signal = $this->blockSignalDetector->detect($eventData);
        if ($signal !== null) {
            $status = (int) ($signal['http_status'] ?? 0);
            $level = $status === 403 ? 'error' : 'warning';

            $this->emitToObs('crawler.target.block_signal', $signal, $level, 'Crawler block signal detected');
            $this->emitToObs('crawler.target.cooldown_applied', $signal, 'info', 'Crawler cooldown recommended');
        }
    }

    private function buildContext(Job $job, ?string $connectionName): array
    {
        $payload = $job->payload();
        $jobName = (string) ($payload['displayName'] ?? $payload['job'] ?? $job->resolveName());
        $queue = method_exists($job, 'getQueue') ? (string) $job->getQueue() : (string) ($payload['queue'] ?? 'default');
        $attempt = max(1, (int) ($job->attempts() ?? 1));
        $jobUuid = (string) ($payload['uuid'] ?? sha1($jobName.'|'.$queue.'|'.$attempt.'|'.json_encode($payload)));

        [$commandData, $source, $site, $url] = $this->extractCommandContext($payload, $jobName);

        return [
            'job_uuid' => $jobUuid,
            'job_name' => $jobName,
            'queue' => $queue,
            'connection' => $connectionName,
            'attempt' => $attempt,
            'site' => $site,
            'source' => $source,
            'url' => $url,
            'second_bucket' => now('UTC')->startOfSecond(),
            'worker_host' => gethostname() ?: php_uname('n'),
            'payload_meta' => $commandData,
        ];
    }

    private function extractCommandContext(array $payload, string $jobName): array
    {
        $commandData = $this->deserializeCommandData($payload);
        $source = $this->firstStringByFields($commandData, (array) config('core.job_telemetry.site_fields', []));
        $url = $this->firstStringByFields($commandData, (array) config('core.job_telemetry.url_fields', []));
        $site = $this->resolveSite($source, $url, $jobName);

        return [$commandData, $source ?? $site, $site, $url];
    }

    private function deserializeCommandData(array $payload): array
    {
        $commandData = [];
        $serializedCommand = Arr::get($payload, 'data.command');
        if (is_string($serializedCommand)) {
            try {
                $command = unserialize($serializedCommand, ['allowed_classes' => true]);
                if (is_object($command)) {
                    $commandData = $this->extractScalarProperties($command);
                }
            } catch (Throwable) {
                // ignore unserialize issues and fallback to payload-based inference
            }
        }

        return $commandData;
    }

    private function firstStringByFields(array $data, array $fields): ?string
    {
        foreach ($fields as $field) {
            $candidate = Arr::get($data, $field);
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveSite(?string $source, ?string $url, string $jobName): string
    {
        $site = 'unknown';

        if (is_string($source) && $source !== '') {
            $site = $source;
        }

        if ($site === 'unknown' && is_string($url) && $url !== '') {
            $host = parse_url($url, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $site = $host;
            }
        }

        if ($site === 'unknown') {
            $mappedSite = config('core.job_telemetry.site_map_by_job.'.$jobName);
            if (is_string($mappedSite) && $mappedSite !== '') {
                $site = $mappedSite;
            }
        }

        return $site;
    }

    private function extractScalarProperties(object $command): array
    {
        $properties = [];
        foreach (get_object_vars($command) as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $properties[$key] = $value;
            }
        }

        return $properties;
    }

    private function computeDurationMs(array $context): ?int
    {
        $startedAtMs = Cache::pull($this->timerCacheKey($context));
        if (! is_numeric($startedAtMs)) {
            return null;
        }

        return max(0, $this->nowMs() - (int) $startedAtMs);
    }

    private function trackRate(array $context, \Illuminate\Support\Carbon $timestamp): void
    {
        if (! (bool) config('core.job_telemetry.rate.enabled', true)) {
            return;
        }

        $site = (string) ($context['site'] ?? 'unknown');
        $bucket = $timestamp->copy()->startOfSecond()->timestamp;
        $rateKey = self::RATE_CACHE_PREFIX.':'.$site.':'.$bucket;

        Cache::add($rateKey, 0, now()->addSeconds(120));
        $jobsPerSecond = (int) Cache::increment($rateKey);

        if ((bool) config('core.job_telemetry.snapshot.enabled', true)) {
            $snapshotKey = implode(':', [
                self::SNAPSHOT_CACHE_PREFIX,
                (string) ($context['connection'] ?? 'unknown'),
                (string) ($context['queue'] ?? 'default'),
                (string) $bucket,
            ]);

            if (Cache::add($snapshotKey, 1, now()->addSeconds(120))) {
                $snapshot = $this->queueSnapshotBuilder->build($context, $jobsPerSecond);
                $this->emitToObs('queue.snapshot', $snapshot, 'info', 'Queue snapshot recorded');
            }
        }

        $criticalThreshold = $this->thresholdForSite($site, 'critical');
        $warningThreshold = $this->thresholdForSite($site, 'warning');
        $level = null;

        if ($jobsPerSecond >= $criticalThreshold) {
            $level = 'critical';
        } elseif ($jobsPerSecond >= $warningThreshold) {
            $level = 'warning';
        }

        if ($level === null) {
            return;
        }

        $alertKey = self::RATE_ALERT_CACHE_PREFIX.':'.$site.':'.$bucket.':'.$level;
        if (! Cache::add($alertKey, 1, now()->addSeconds(120))) {
            return;
        }

        $eventData = [
            'event_type' => 'rate_limit_exceeded',
            'status' => $level,
            'timestamp' => $timestamp,
            'second_bucket' => $timestamp->copy()->startOfSecond(),
            'site' => $site,
            'jobs_per_second' => $jobsPerSecond,
            'warning_threshold' => $warningThreshold,
            'critical_threshold' => $criticalThreshold,
            'job_name' => $context['job_name'] ?? null,
            'queue' => $context['queue'] ?? null,
            'connection' => $context['connection'] ?? null,
            'worker_host' => $context['worker_host'] ?? null,
        ];

        $this->writeEvent($eventData, $timestamp);

        $this->emitToObs('queue.rate_limit_exceeded', $eventData, $level === 'critical' ? 'error' : 'warning', 'Queue job rate threshold exceeded');
    }

    private function thresholdForSite(string $site, string $level): int
    {
        $default = (int) config('core.job_telemetry.rate.'.$level.'_per_second', $level === 'critical' ? 40 : 20);
        $siteOverride = config('core.job_telemetry.site_thresholds.'.$site.'.'.$level);

        if (! is_numeric($siteOverride)) {
            return $default;
        }

        return max(1, (int) $siteOverride);
    }

    private function writeEvent(array $data, \Illuminate\Support\Carbon $timestamp): void
    {
        try {
            $this->ensureIndexesOnce();

            JobTelemetryEvent::query()->create([
                ...$data,
                'expire_at' => $timestamp->copy()->addDays((int) config('core.job_telemetry.retention_days', 30)),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Unable to persist job telemetry event', [
                'error' => $exception->getMessage(),
                'event_type' => $data['event_type'] ?? null,
                'job_name' => $data['job_name'] ?? null,
            ]);
        }
    }

    private function ensureIndexesOnce(): void
    {
        if (self::$indexesEnsured || ! (bool) config('core.job_telemetry.auto_create_indexes', true)) {
            return;
        }

        self::$indexesEnsured = true;

        try {
            JobTelemetryEvent::query()->raw(function ($collection): void {
                $collection->createIndexes([
                    ['key' => ['expire_at' => 1], 'name' => 'job_events_expire_at_ttl', 'expireAfterSeconds' => 0],
                    ['key' => ['timestamp' => -1], 'name' => 'job_events_timestamp_desc'],
                    ['key' => ['status' => 1, 'timestamp' => -1], 'name' => 'job_events_status_timestamp'],
                    ['key' => ['site' => 1, 'timestamp' => -1], 'name' => 'job_events_site_timestamp'],
                    ['key' => ['job_name' => 1, 'timestamp' => -1], 'name' => 'job_events_job_name_timestamp'],
                    ['key' => ['second_bucket' => 1, 'site' => 1], 'name' => 'job_events_second_bucket_site'],
                    ['key' => ['job_uuid' => 1], 'name' => 'job_events_job_uuid'],
                ]);
            });
        } catch (Throwable $exception) {
            Log::warning('Unable to create Mongo indexes for job telemetry', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function timerCacheKey(array $context): string
    {
        return implode(':', [
            self::TIMER_CACHE_PREFIX,
            (string) ($context['connection'] ?? 'unknown'),
            (string) ($context['queue'] ?? 'default'),
            (string) ($context['job_uuid'] ?? 'unknown'),
            (string) ($context['attempt'] ?? 1),
        ]);
    }

    private function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    private function isEnabled(): bool
    {
        return (bool) config('core.job_telemetry.enabled', true);
    }

    private function emitToObs(string $eventType, array $context, string $level, string $message): void
    {
        try {
            app(TelemetryEmitterInterface::class)->emit($eventType, $context, $level, $message);
        } catch (Throwable $exception) {
            Log::warning('Unable to emit queue telemetry to OBS', [
                'event_type' => $eventType,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
