<?php

namespace Modules\Core\Observability;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Core\Observability\Contracts\ObservabilityClientInterface;
use Modules\Core\Observability\Exceptions\ObsConfigurationException;
use Modules\Core\Observability\Exceptions\ObsNonRetryableException;

class ObsHttpClient implements ObservabilityClientInterface
{
    public function sendLog(array $payload): void
    {
        if (! (bool) config('services.obs.enabled', false)) {
            return;
        }

        $baseUrl = trim((string) config('services.obs.base_url', ''));
        $apiKey = trim((string) config('services.obs.api_key', ''));

        if ($baseUrl === '' || $apiKey === '') {
            throw new ObsConfigurationException('OBS is enabled but OBS_BASE_URL or OBS_API_KEY is missing.');
        }

        $timeout = (float) config('services.obs.timeout_seconds', 2);
        $idempotencyKey = sha1((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));

        $response = Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'x-api-key' => $apiKey,
                'x-idempotency-key' => $idempotencyKey,
            ])
            ->timeout($timeout)
            ->post(rtrim($baseUrl, '/').'/logs', $payload);

        $this->guardResponse($response);
    }

    private function guardResponse(Response $response): void
    {
        $status = $response->status();
        $successStatuses = (array) config('services.obs.success_statuses', [200, 201, 202]);
        $nonRetryableStatuses = (array) config('services.obs.non_retryable_statuses', [400, 401, 403, 413, 422]);

        if (in_array($status, $successStatuses, true)) {
            $requiredKey = trim((string) config('services.obs.required_response_key', ''));

            if ($requiredKey !== '' && $response->json($requiredKey) === null) {
                throw new ObsNonRetryableException('OBS success response missing required key: '.$requiredKey);
            }

            return;
        }

        if (in_array($status, $nonRetryableStatuses, true)) {
            throw new ObsNonRetryableException('OBS rejected telemetry with non-retryable status '.$status.'.');
        }

        $response->throw();
    }
}
