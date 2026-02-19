<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use Modules\Core\Services\AnalyticsIngestService;
use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class MovieControllerContractTest extends TestCase
{
    public function test_movie_view_happy_increments_and_returns_views(): void
    {
        $jav = Jav::factory()->create(['views' => 2]);

        $this->postJson(route('jav.movies.view', $jav))
            ->assertOk()
            ->assertJsonStructure(['views'])
            ->assertJsonPath('views', 3);
    }

    public function test_movie_view_unhappy_returns_not_found_for_missing_movie(): void
    {
        $this->postJson('/jav/movies/999999/view')->assertNotFound();
    }

    public function test_movie_view_security_is_accessible_without_auth_but_only_updates_target_movie(): void
    {
        $target = Jav::factory()->create(['views' => 10]);
        $other = Jav::factory()->create(['views' => 20]);

        $this->postJson(route('jav.movies.view', $target))
            ->assertOk()
            ->assertJsonPath('views', 11);

        $this->assertSame(11, (int) $target->fresh()->views);
        $this->assertSame(20, (int) $other->fresh()->views);
    }

    public function test_movie_view_weird_case_handles_zero_initial_views(): void
    {
        $jav = Jav::factory()->create(['views' => 0]);

        $this->postJson(route('jav.movies.view', $jav))
            ->assertOk()
            ->assertJsonPath('views', 1);
    }

    public function test_movie_view_exploit_case_rejects_non_numeric_route_key_payload(): void
    {
        $this->postJson('/jav/movies/1%20OR%201=1/view')->assertNotFound();
    }

    public function test_movie_view_uses_ingest_service_when_analytics_enabled(): void
    {
        config(['analytics.enabled' => true]);

        $jav = Jav::factory()->create(['views' => 2]);

        $service = \Mockery::mock(AnalyticsIngestService::class);
        $service->shouldReceive('ingest')
            ->once()
            ->withArgs(function (array $payload) use ($jav): bool {
                return ($payload['entity_id'] ?? null) === $jav->uuid
                    && ($payload['action'] ?? null) === 'view';
            });
        $this->app->instance(AnalyticsIngestService::class, $service);

        $this->postJson(route('jav.movies.view', $jav))
            ->assertOk()
            ->assertJsonPath('views', 2);

        $this->assertSame(2, (int) $jav->fresh()->views);
    }
}
