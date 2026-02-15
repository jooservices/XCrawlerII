<?php

namespace Modules\JAV\Tests\Feature\Commands;

use App\Models\User;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class JavSyncRecommendationsCommandTest extends TestCase
{
    public function test_command_syncs_specific_user_ids_when_option_is_provided(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->artisan('jav:sync:recommendations', [
            '--user-id' => [$userA->id, $userB->id, 999999, $userA->id],
            '--limit' => 12,
        ])
            ->expectsOutput("Synced recommendation snapshot for user {$userA->id}.")
            ->expectsOutput("Synced recommendation snapshot for user {$userB->id}.")
            ->expectsOutput('Recommendation sync completed. Synced users: 2.')
            ->assertExitCode(0);
    }

    public function test_command_syncs_only_users_with_favorites_when_no_user_ids_provided(): void
    {
        $jav = Jav::factory()->create();
        $withFavorite = User::factory()->create();
        $withoutFavorite = User::factory()->create();

        Favorite::query()->create([
            'user_id' => $withFavorite->id,
            'favoritable_id' => $jav->id,
            'favoritable_type' => Jav::class,
        ]);

        $this->artisan('jav:sync:recommendations')
            ->expectsOutput('Recommendation sync completed. Synced users: 1.')
            ->assertExitCode(0);

        $this->assertNotSame($withFavorite->id, $withoutFavorite->id);
    }
}
