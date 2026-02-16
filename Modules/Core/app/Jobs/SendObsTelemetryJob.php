<?php

namespace Modules\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Core\Observability\Contracts\ObservabilityClientInterface;
use Modules\Core\Observability\Exceptions\ObsConfigurationException;
use Modules\Core\Observability\Exceptions\ObsNonRetryableException;
use Throwable;

class SendObsTelemetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public array $payload) {}

    public function handle(ObservabilityClientInterface $client): void
    {
        try {
            $client->sendLog($this->payload);
        } catch (ObsNonRetryableException|ObsConfigurationException $exception) {
            $this->failed($exception);
        }
    }

    public function backoff(): array
    {
        return [1, 2, 5];
    }

    public function failed(Throwable $exception): void
    {
        [$failureType, $statusCode] = $this->classifyFailure($exception);

        Log::channel('single')->warning('OBS telemetry delivery permanently failed', [
            'error' => $exception->getMessage(),
            'event_type' => $this->payload['eventType'] ?? null,
            'obs_failure_type' => $failureType,
            'obs_status_code' => $statusCode,
            'metric_key' => 'obs_delivery_failure_total',
            '__obs_skip' => true,
        ]);
    }

    /**
     * @return array{0:string,1:int|null}
     */
    private function classifyFailure(Throwable $exception): array
    {
        $classification = ['unknown', null];

        if ($exception instanceof ObsNonRetryableException) {
            $classification = ['non_retryable', null];
        } elseif ($exception instanceof ObsConfigurationException) {
            $classification = ['configuration', null];
        } elseif ($exception instanceof RequestException) {
            $classification = ['transient', $exception->response?->status()];
        }

        return $classification;
    }
}
