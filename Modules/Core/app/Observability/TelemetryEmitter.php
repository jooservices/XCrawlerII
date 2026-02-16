<?php

namespace Modules\Core\Observability;

use Illuminate\Support\Facades\Log;
use Modules\Core\Jobs\SendObsTelemetryJob;
use Modules\Core\Observability\Contracts\ObservabilityClientInterface;
use Modules\Core\Observability\Contracts\TelemetryEmitterInterface;
use Throwable;

class TelemetryEmitter implements TelemetryEmitterInterface
{
    public function __construct(
        private readonly ObsPayloadMapper $payloadMapper,
        private readonly ObservabilityClientInterface $client,
    ) {}

    public function emit(string $eventType, array $context = [], string $level = 'info', ?string $message = null): void
    {
        if (! (bool) config('services.obs.enabled', false)) {
            return;
        }

        $payload = $this->payloadMapper->map($eventType, $context, $level, $message);

        try {
            if ((bool) config('services.obs.queue_enabled', true)) {
                SendObsTelemetryJob::dispatch($payload)
                    ->onQueue((string) config('services.obs.queue_name', 'obs-telemetry'));

                return;
            }

            $this->client->sendLog($payload);
        } catch (Throwable $exception) {
            Log::channel('single')->warning('Unable to emit OBS telemetry event', [
                'error' => $exception->getMessage(),
                'event_type' => $eventType,
                '__obs_skip' => true,
            ]);
        }
    }
}
