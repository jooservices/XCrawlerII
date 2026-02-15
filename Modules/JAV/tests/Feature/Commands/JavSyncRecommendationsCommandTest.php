<?php

namespace Modules\JAV\Tests\Feature\Commands;

use App\Models\User;
use Mockery;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\RecommendationService;
use Modules\JAV\Tests\TestCase;

class JavSyncRecommendationsCommandTest extends TestCase
{
    public function test_command_syncs_specific_user_ids_when_option_is_provided(): void
    {
        $service = Mockery::mock(RecommendationService::class);
        $service->shouldReceive('syncSnapshotForUserId')->once()->with(5, 12)->andReturn(true);
        $service->shouldReceive('syncSnapshotForUserId')->once()->with(7, 12)->andReturn(false);
        $this->app->instance(RecommendationService::class, $service);

        $this->artisan('jav:sync:recommendations', [
            '--user-id' => [5, 7, 5],
            '--limit' => 12,
        ])->assertExitCode(0);
    }

    public function test_command_syncs_only_users_with_favorites_when_no_user_ids_provided(): void
    {
        $jav = Jav::factory()->create();
        $withFavorite = User::factory()->create();
        $withoutFavorite = User::factory()->create();

        \Modules\JAV\Models\Favorite::query()->create([
            'user_id' => $withFavorite->id,
            'favoritable_id' => $jav->id,
            'favoritable_type' => Jav::class,
        ]);

        $service = Mockery::mock(RecommendationService::class);
        $service->shouldReceive('syncSnapshotForUserId')
            ->once()
            ->with((int) $withFavorite->id, 30)
            ->andReturn(true);
        $service->shouldNotReceive('syncSnapshotForUserId')
            ->with((int) $withoutFavorite->id, 30);
        $this->app->instance(RecommendationService::class, $service);

        $this->artisan('jav:sync:recommendations')->assertExitCode(0);
    }
}
