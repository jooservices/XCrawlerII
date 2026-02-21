<?php

namespace Modules\Core\Tests\Unit;

use Illuminate\Support\Facades\RateLimiter;
use Modules\Core\Observability\Contracts\ObservabilityClientInterface;
use Modules\Core\Observability\Contracts\TelemetryEmitterInterface;
use Modules\Core\Observability\ObsHttpClient;
use Modules\Core\Observability\TelemetryEmitter;
use Modules\Core\Services\ConfigService;
use Modules\Core\Tests\TestCase;

class CoreServiceProviderTest extends TestCase
{
    public function test_observability_client_interface_resolves_to_obs_http_client(): void
    {
        $resolved = $this->app->make(ObservabilityClientInterface::class);

        $this->assertInstanceOf(ObsHttpClient::class, $resolved);
    }

    public function test_telemetry_emitter_interface_is_singleton(): void
    {
        $first = $this->app->make(TelemetryEmitterInterface::class);
        $second = $this->app->make(TelemetryEmitterInterface::class);

        $this->assertInstanceOf(TelemetryEmitter::class, $first);
        $this->assertSame($first, $second);
    }

    public function test_config_service_bound_as_singleton(): void
    {
        $first = $this->app->make(ConfigService::class);
        $second = $this->app->make(ConfigService::class);

        $this->assertInstanceOf(ConfigService::class, $first);
        $this->assertSame($first, $second);
    }

    public function test_config_service_alias_resolves(): void
    {
        $fromAlias = $this->app->make('core.config');
        $fromClass = $this->app->make(ConfigService::class);

        $this->assertInstanceOf(ConfigService::class, $fromAlias);
        $this->assertSame($fromAlias, $fromClass);
    }

    public function test_analytics_rate_limiter_is_registered(): void
    {
        // The rate limiter 'analytics' should be registered by CoreServiceProvider
        $limiter = RateLimiter::limiter('analytics');

        $this->assertNotNull($limiter, 'Analytics rate limiter should be registered');
    }
}
