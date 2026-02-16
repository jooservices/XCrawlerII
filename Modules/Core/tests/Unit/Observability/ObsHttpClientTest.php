<?php

namespace Modules\Core\Tests\Unit\Observability;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Modules\Core\Observability\Exceptions\ObsConfigurationException;
use Modules\Core\Observability\Exceptions\ObsNonRetryableException;
use Modules\Core\Observability\ObsHttpClient;
use Tests\TestCase;

class ObsHttpClientTest extends TestCase
{
    private const OBS_URL = 'https://obs.example.com';

    private const OBS_LOGS_PATH = '/logs';

    public function test_it_sends_log_to_obs_with_api_key_header(): void
    {
        config([
            'services.obs.enabled' => true,
            'services.obs.base_url' => self::OBS_URL,
            'services.obs.api_key' => 'obs-key-123',
            'services.obs.timeout_seconds' => 2,
            'services.obs.retry_times' => 1,
            'services.obs.retry_sleep_ms' => 0,
        ]);

        Http::fake([
            self::OBS_URL.self::OBS_LOGS_PATH => Http::response([], 202),
        ]);

        $client = app(ObsHttpClient::class);
        $client->sendLog([
            'service' => 'xcrawlerii',
            'env' => 'testing',
            'level' => 'INFO',
            'message' => 'test',
            'timestamp' => now('UTC')->toIso8601String(),
        ]);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === self::OBS_URL.self::OBS_LOGS_PATH
                && $request->hasHeader('x-api-key', 'obs-key-123')
                && $request->hasHeader('x-idempotency-key')
                && $request->method() === 'POST';
        });
    }

    public function test_it_does_not_send_when_obs_is_disabled(): void
    {
        config([
            'services.obs.enabled' => false,
            'services.obs.base_url' => self::OBS_URL,
            'services.obs.api_key' => 'obs-key-123',
        ]);

        Http::fake();

        $client = app(ObsHttpClient::class);
        $client->sendLog([
            'service' => 'xcrawlerii',
            'env' => 'testing',
            'level' => 'INFO',
            'message' => 'test',
            'timestamp' => now('UTC')->toIso8601String(),
        ]);

        Http::assertNothingSent();
    }

    public function test_it_throws_configuration_exception_when_base_url_or_api_key_is_missing(): void
    {
        config([
            'services.obs.enabled' => true,
            'services.obs.base_url' => '',
            'services.obs.api_key' => '',
        ]);

        $this->expectException(ObsConfigurationException::class);

        app(ObsHttpClient::class)->sendLog([
            'service' => 'xcrawlerii',
            'env' => 'testing',
            'level' => 'INFO',
            'message' => 'test',
            'timestamp' => now('UTC')->toIso8601String(),
        ]);
    }

    public function test_it_throws_on_unsuccessful_obs_response(): void
    {
        config([
            'services.obs.enabled' => true,
            'services.obs.base_url' => self::OBS_URL,
            'services.obs.api_key' => 'obs-key-123',
            'services.obs.retry_times' => 1,
            'services.obs.retry_sleep_ms' => 0,
        ]);

        Http::fake([
            self::OBS_URL.self::OBS_LOGS_PATH => Http::response(['error' => 'down'], 500),
        ]);

        $this->expectException(RequestException::class);

        app(ObsHttpClient::class)->sendLog([
            'service' => 'xcrawlerii',
            'env' => 'testing',
            'level' => 'INFO',
            'message' => 'test',
            'timestamp' => now('UTC')->toIso8601String(),
        ]);
    }

    public function test_it_throws_non_retryable_exception_for_invalid_api_key_status(): void
    {
        config([
            'services.obs.enabled' => true,
            'services.obs.base_url' => self::OBS_URL,
            'services.obs.api_key' => 'obs-key-123',
            'services.obs.non_retryable_statuses' => [401],
        ]);

        Http::fake([
            self::OBS_URL.self::OBS_LOGS_PATH => Http::response(['error' => 'unauthorized'], 401),
        ]);

        $this->expectException(ObsNonRetryableException::class);

        app(ObsHttpClient::class)->sendLog([
            'service' => 'xcrawlerii',
            'env' => 'testing',
            'level' => 'INFO',
            'message' => 'test',
            'timestamp' => now('UTC')->toIso8601String(),
        ]);
    }

    public function test_it_throws_non_retryable_exception_when_required_response_key_is_missing(): void
    {
        config([
            'services.obs.enabled' => true,
            'services.obs.base_url' => self::OBS_URL,
            'services.obs.api_key' => 'obs-key-123',
            'services.obs.required_response_key' => 'ok',
        ]);

        Http::fake([
            self::OBS_URL.self::OBS_LOGS_PATH => Http::response(['status' => 'accepted'], 202),
        ]);

        $this->expectException(ObsNonRetryableException::class);

        app(ObsHttpClient::class)->sendLog([
            'service' => 'xcrawlerii',
            'env' => 'testing',
            'level' => 'INFO',
            'message' => 'test',
            'timestamp' => now('UTC')->toIso8601String(),
        ]);
    }
}
