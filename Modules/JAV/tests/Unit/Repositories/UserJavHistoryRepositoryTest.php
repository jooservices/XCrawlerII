<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserJavHistory;
use Modules\JAV\Repositories\UserJavHistoryRepository;
use Tests\TestCase;

class UserJavHistoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_continue_watching_returns_unique_javs_with_limit(): void
    {
        $user = User::factory()->create();
        $jav1 = Jav::factory()->create();
        $jav2 = Jav::factory()->create();

        UserJavHistory::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav1->id,
            'action' => 'download',
            'updated_at' => now()->subMinutes(10),
        ]);
        UserJavHistory::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav1->id,
            'action' => 'view',
            'updated_at' => now()->subMinutes(5),
        ]);
        UserJavHistory::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav2->id,
            'action' => 'view',
            'updated_at' => now()->subMinutes(1),
        ]);

        $result = app(UserJavHistoryRepository::class)->continueWatching($user->id, 8);

        $this->assertCount(2, $result);
        $this->assertSame($jav2->id, $result->first()->jav_id);
    }

    public function test_paginate_for_user_returns_only_user_rows(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        UserJavHistory::factory()->create(['user_id' => $user->id]);
        UserJavHistory::factory()->create(['user_id' => $otherUser->id]);

        $page = app(UserJavHistoryRepository::class)->paginateForUser($user->id, 30);

        $this->assertCount(1, $page->items());
        $this->assertSame($user->id, $page->items()[0]->user_id);
    }

    public function test_record_view_creates_only_one_view_row(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $repository = app(UserJavHistoryRepository::class);

        $repository->recordView($user->id, $jav->id);
        $repository->recordView($user->id, $jav->id);

        $this->assertSame(1, UserJavHistory::query()
            ->where('user_id', $user->id)
            ->where('jav_id', $jav->id)
            ->where('action', 'view')
            ->count());
    }

    public function test_record_download_upserts_download_row(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $repository = app(UserJavHistoryRepository::class);

        $repository->recordDownload($user->id, $jav->id);
        $repository->recordDownload($user->id, $jav->id);

        $this->assertSame(1, UserJavHistory::query()
            ->where('user_id', $user->id)
            ->where('jav_id', $jav->id)
            ->where('action', 'download')
            ->count());
    }
}
